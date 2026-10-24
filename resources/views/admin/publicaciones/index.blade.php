@extends('layouts.admin')

@section('titulo', 'Publicaciones')
@section('subtitulo', $pendientes
    ? $pendientes . ' ' . \App\Support\Texto::plural('publicación', $pendientes) . ' esperando revisión'
    : 'No hay nada esperando revisión')

@section('acciones')
    <a class="hb-btn hb-btn--ghost" href="{{ route('admin.exportar', 'publicaciones') }}">Descargar CSV</a>
@endsection

@section('contenido')
<div class="hb-card">
    <div class="hb-card__header hb-card__header--apilado">

        {{-- Pestañas de estado. Antes esto estaba repartido entre dos
             pantallas del menú: aquí están todas las publicaciones y lo
             único que cambia es por cuál se filtra. --}}
        <div class="hb-row" style="flex-wrap:wrap">
            @foreach (\App\Models\Alojamiento::ESTADOS as $clave => $texto)
                <a class="hb-btn hb-btn--sm {{ $estado === $clave ? 'hb-btn--primary' : 'hb-btn--ghost' }}"
                   href="{{ route('admin.publicaciones.index', ['estado' => $clave, 'q' => request('q')]) }}">
                    {{ $texto }}
                    @if ($clave === \App\Models\Alojamiento::PENDIENTE && $pendientes)
                        <span class="hb-badge hb-badge--danger">{{ $pendientes }}</span>
                    @endif
                </a>
            @endforeach

            <a class="hb-btn hb-btn--sm {{ $estado === 'pausadas' ? 'hb-btn--primary' : 'hb-btn--ghost' }}"
               href="{{ route('admin.publicaciones.index', ['estado' => 'pausadas', 'q' => request('q')]) }}">
                Pausadas
                @if ($pausadas)<span class="hb-badge hb-badge--muted">{{ $pausadas }}</span>@endif
            </a>

            <a class="hb-btn hb-btn--sm {{ $estado === 'todas' ? 'hb-btn--primary' : 'hb-btn--ghost' }}"
               href="{{ route('admin.publicaciones.index', ['estado' => 'todas', 'q' => request('q')]) }}">Todas</a>
        </div>

        <form method="GET" action="{{ route('admin.publicaciones.index') }}" class="hb-row">
            <input type="hidden" name="estado" value="{{ $estado }}">
            <label class="hb-sr-only" for="q">Buscar publicación</label>
            <input class="hb-input hb-input--sm" type="search" id="q" name="q" value="{{ request('q') }}"
                   placeholder="Título, ciudad o barrio" style="min-width:220px">
            <button class="hb-btn hb-btn--ghost hb-btn--sm" type="submit">Buscar</button>
            @if (request('q'))
                <a class="hb-btn hb-btn--link"
                   href="{{ route('admin.publicaciones.index', ['estado' => $estado]) }}">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="hb-table-wrap">
        <table class="hb-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Publicación</th>
                    <th>Arrendador</th>
                    <th>Enviada</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($alojamientos as $a)
                    <tr>
                        <td style="width:60px"><img class="hb-table__thumb" src="{{ $a->coverUrl() }}" alt=""></td>
                        <td>
                            <strong>{{ Str::limit($a->title, 48) }}</strong>
                            <div class="hb-muted hb-small">
                                {{ $a->precioFormateado() }}/{{ $a->price_period }}
                                · {{ $a->city }}@if ($a->neighborhood), {{ $a->neighborhood }}@endif
                            </div>
                        </td>
                        <td class="hb-small">
                            {{ $a->user->name }}
                            @if ($a->user->estaVerificado())
                                <span class="hb-badge hb-badge--success">verificado</span>
                            @else
                                <span class="hb-badge hb-badge--warning">sin verificar</span>
                            @endif
                        </td>
                        <td class="hb-muted hb-small">{{ $a->created_at->diffForHumans() }}</td>
                        <td>
                            <span class="hb-badge {{ $a->estaPendiente() ? 'hb-badge--primary' : ($a->estaAprobada() ? 'hb-badge--success' : 'hb-badge--danger') }}">
                                {{ $a->nombreEstado() }}
                            </span>
                            @if ($a->estaAprobada() && ! $a->is_active)
                                <span class="hb-badge hb-badge--muted">pausada</span>
                            @endif
                            @if ($a->tieneCampanaActiva())
                                <span class="hb-badge hb-badge--primary">★ campaña</span>
                            @endif
                        </td>
                        <td>
                            {{-- Cada acción aparece cuando tiene sentido.
                                 Pausar algo que todavía nadie aprobó, o
                                 aprobar lo que ya está aprobado, solo
                                 llena la fila de botones inútiles. --}}
                            <div class="hb-table__actions">
                                <a class="hb-btn hb-btn--ghost hb-btn--sm"
                                   href="{{ route('admin.publicaciones.show', $a) }}">
                                    {{ $a->estaPendiente() ? 'Revisar' : 'Ver' }}
                                </a>

                                @if ($a->estaAprobada())
                                    <form method="POST" action="{{ route('admin.publicaciones.toggle', $a) }}">
                                        @csrf @method('PATCH')
                                        <button class="hb-btn hb-btn--ghost hb-btn--sm" type="submit">
                                            {{ $a->is_active ? 'Pausar' : 'Activar' }}
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.publicaciones.destroy', $a) }}"
                                      data-confirmar="¿Eliminar «{{ $a->title }}»? Se borrarán sus fotos y reseñas. No se puede deshacer.">
                                    @csrf @method('DELETE')
                                    <button class="hb-btn hb-btn--danger hb-btn--sm" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="hb-center hb-muted" style="padding:2.5rem">
                        @if (request('q'))
                            Nada coincide con «{{ request('q') }}» en este estado.
                        @else
                            No hay publicaciones en este estado.
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($alojamientos->hasPages())
        <div class="hb-card__footer">{{ $alojamientos->links() }}</div>
    @endif
</div>
@endsection
