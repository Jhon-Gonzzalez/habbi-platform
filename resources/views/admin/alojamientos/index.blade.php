@extends('layouts.admin')

@section('titulo', 'Alojamientos')
@section('subtitulo', $alojamientos->total() . ' ' . \App\Support\Texto::plural('publicación', $alojamientos->total()) . ' en la plataforma')

@section('contenido')
<div class="hb-card">
    <div class="hb-card__header">
        <form method="GET" action="{{ route('admin.alojamientos.index') }}" class="hb-row">
            <label class="hb-sr-only" for="q">Buscar alojamiento</label>
            <input class="hb-input" type="search" id="q" name="q" value="{{ request('q') }}"
                   placeholder="Buscar por título o ciudad" style="min-width:240px">

            <label class="hb-sr-only" for="estado">Estado</label>
            <select class="hb-select" id="estado" name="estado" data-autosubmit style="width:auto">
                <option value="">Todos</option>
                <option value="activos"  @selected(request('estado') === 'activos')>Solo activos</option>
                <option value="pausados" @selected(request('estado') === 'pausados')>Solo pausados</option>
            </select>

            <button class="hb-btn hb-btn--ghost hb-btn--sm" type="submit">Buscar</button>
            @if (request('q') || request('estado'))
                <a class="hb-btn hb-btn--link" href="{{ route('admin.alojamientos.index') }}">Limpiar</a>
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
                    <th>Precio</th>
                    <th>Calificación</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($alojamientos as $a)
                    <tr>
                        <td style="width:60px"><img class="hb-table__thumb" src="{{ $a->coverUrl() }}" alt=""></td>
                        <td>
                            <a href="{{ route('alojamientos.show', $a) }}">{{ Str::limit($a->title, 38) }}</a>
                            <div class="hb-muted hb-small">{{ $a->type }} · {{ $a->city }}</div>
                        </td>
                        <td>
                            <a href="{{ route('admin.usuarios.show', $a->user_id) }}">{{ $a->user->name }}</a>
                            <div class="hb-muted hb-small">{{ $a->user->email }}</div>
                        </td>
                        <td style="white-space:nowrap">
                            {{ $a->precioFormateado() }}
                            <div class="hb-muted hb-small">por {{ $a->price_period }}</div>
                        </td>
                        <td><x-estrellas :valor="$a->promedio()" :total="$a->totalResenas()" /></td>
                        <td>
                            <span class="hb-badge {{ $a->is_active ? 'hb-badge--success' : 'hb-badge--muted' }}">
                                {{ $a->is_active ? 'Activo' : 'Pausado' }}
                            </span>
                        </td>
                        <td>
                            <div class="hb-table__actions">
                                <form method="POST" action="{{ route('admin.alojamientos.toggle', $a) }}">
                                    @csrf @method('PATCH')
                                    <button class="hb-btn hb-btn--ghost hb-btn--sm" type="submit">
                                        {{ $a->is_active ? 'Pausar' : 'Activar' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.alojamientos.destroy', $a) }}"
                                      data-confirmar="¿Eliminar «{{ $a->title }}»? Se borrarán sus fotos y reseñas.">
                                    @csrf @method('DELETE')
                                    <button class="hb-btn hb-btn--danger hb-btn--sm" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="hb-muted hb-center" style="padding:2.5rem">No se encontraron alojamientos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="hb-pagination">{{ $alojamientos->links() }}</div>
@endsection
