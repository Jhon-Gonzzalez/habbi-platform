@extends('layouts.admin')

@section('titulo', 'Auditoría')
@section('subtitulo', 'Qué se hizo, quién lo hizo y cuándo')

@section('contenido')

<div class="hb-alert hb-alert--info" style="margin-bottom:1.5rem">
    <span class="hb-alert__icon" aria-hidden="true">i</span>
    <span>
        Este registro no se puede editar ni borrar desde la aplicación, a propósito:
        un registro que el vigilado puede limpiar no vigila nada. Tampoco guarda
        contraseñas ni los enlaces de restablecimiento, solo que se generaron.
    </span>
</div>

<div class="hb-card">
    <div class="hb-card__header">
        <form method="GET" action="{{ route('admin.auditoria.index') }}" class="hb-row" style="gap:.6rem;flex-wrap:wrap">
            <select class="hb-input hb-input--sm" name="accion" style="width:auto">
                <option value="">Todas las acciones</option>
                @foreach ($acciones as $valor => $texto)
                    <option value="{{ $valor }}" @selected($accion === $valor)>{{ $texto }}</option>
                @endforeach
            </select>

            <select class="hb-input hb-input--sm" name="actor" style="width:auto">
                <option value="">Cualquier administrador</option>
                @foreach ($actores as $a)
                    <option value="{{ $a->id }}" @selected($actor === $a->id)>{{ $a->name }}</option>
                @endforeach
            </select>

            <button class="hb-btn hb-btn--primary hb-btn--sm" type="submit">Filtrar</button>

            @if ($accion || $actor)
                <a class="hb-btn hb-btn--ghost hb-btn--sm" href="{{ route('admin.auditoria.index') }}">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="hb-table-wrap">
        <table class="hb-table">
            <thead>
                <tr>
                    <th>Cuándo</th>
                    <th>Quién</th>
                    <th>Qué hizo</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registros as $r)
                    <tr>
                        <td class="hb-small">
                            {{ $r->created_at->format('d/m/Y H:i') }}
                            <div class="hb-muted hb-small">{{ $r->created_at->diffForHumans() }}</div>
                        </td>

                        <td class="hb-small">
                            {{-- Se muestra el nombre guardado en la fila, no el de
                                 la relación: si la cuenta se borró, el rastro queda. --}}
                            <strong>{{ $r->actor_nombre }}</strong>
                            <div class="hb-muted hb-small">{{ $r->actor_correo }}</div>
                            @unless ($r->actor)
                                <span class="hb-badge hb-badge--muted">cuenta eliminada</span>
                            @endunless
                        </td>

                        <td>
                            <span class="hb-badge hb-badge--primary">{{ $r->etiqueta() }}</span>
                            <div class="hb-small" style="margin-top:.35rem;max-width:42ch">{{ $r->descripcion }}</div>
                        </td>

                        <td class="hb-muted hb-small">
                            @if ($r->datos)
                                <details>
                                    <summary style="cursor:pointer">Ver datos</summary>
                                    <ul style="margin:.4rem 0 0;padding-left:1.1rem">
                                        @foreach ($r->datos as $clave => $valor)
                                            <li>
                                                <strong>{{ $clave }}:</strong>
                                                {{ is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : (is_bool($valor) ? ($valor ? 'sí' : 'no') : ($valor ?? '—')) }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                            @if ($r->ip)
                                <div style="margin-top:.3rem">IP {{ $r->ip }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="hb-center hb-muted" style="padding:2.5rem">
                            No hay nada registrado todavía
                            @if ($accion || $actor) con esos filtros @endif.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($registros->hasPages())
        <div class="hb-card__footer">{{ $registros->links() }}</div>
    @endif
</div>
@endsection
