@extends('layouts.admin')

@section('titulo', 'Reportes de publicaciones')
@section('subtitulo', $abiertos . ' ' . \App\Support\Texto::plural('reporte', $abiertos) . ' sin revisar')

@section('contenido')
<div class="hb-card">
    <div class="hb-card__header">
        <div class="hb-row">
            @foreach ($estados as $clave => $texto)
                <a class="hb-btn hb-btn--sm {{ $estado === $clave ? 'hb-btn--primary' : 'hb-btn--ghost' }}"
                   href="{{ route('admin.reportes.index', ['estado' => $clave]) }}">
                    {{ $texto }}
                    @if ($clave === \App\Models\Reporte::ESTADO_ABIERTO && $abiertos)
                        <span class="hb-badge hb-badge--danger">{{ $abiertos }}</span>
                    @endif
                </a>
            @endforeach
            <a class="hb-btn hb-btn--sm {{ ! array_key_exists($estado, $estados) ? 'hb-btn--primary' : 'hb-btn--ghost' }}"
               href="{{ route('admin.reportes.index', ['estado' => 'todos']) }}">Todos</a>
        </div>
    </div>

    <div class="hb-table-wrap">
        <table class="hb-table">
            <thead>
                <tr>
                    <th>Publicación</th>
                    <th>Motivo</th>
                    <th>Reportó</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reportes as $r)
                    <tr>
                        <td>
                            <strong>
                                <a href="{{ route('alojamientos.show', $r->alojamiento) }}" target="_blank" rel="noopener">
                                    {{ $r->alojamiento->title }}
                                </a>
                            </strong>
                            <div class="hb-muted hb-small">
                                de {{ $r->alojamiento->user->name }}
                                @unless ($r->alojamiento->is_active)
                                    · <span class="hb-badge hb-badge--warning">pausada</span>
                                @endunless
                            </div>
                        </td>

                        <td>
                            <div class="hb-small"><strong>{{ $r->motivoTexto() }}</strong></div>
                            @if ($r->detalle)
                                <div class="hb-muted hb-small" style="max-width:38ch">{{ $r->detalle }}</div>
                            @endif
                        </td>

                        <td class="hb-small">
                            {{ $r->user->name }}
                            <div class="hb-muted hb-small">{{ $r->created_at->diffForHumans() }}</div>
                        </td>

                        <td>
                            <span class="hb-badge {{ $r->estaAbierto() ? 'hb-badge--danger' : 'hb-badge--success' }}">
                                {{ ucfirst($r->estado) }}
                            </span>
                            @if ($r->revisor)
                                <div class="hb-muted hb-small">por {{ $r->revisor->name }}</div>
                            @endif
                            @if ($r->nota_interna)
                                <div class="hb-muted hb-small" style="max-width:30ch">{{ $r->nota_interna }}</div>
                            @endif
                        </td>

                        <td>
                            @if ($r->estaAbierto())
                                <form method="POST" action="{{ route('admin.reportes.resolver', $r) }}">
                                    @csrf
                                    <input class="hb-input hb-input--sm" type="text" name="nota_interna" aria-label="Nota interna sobre este reporte"
                                           placeholder="Nota (opcional)" maxlength="1000"
                                           style="margin-bottom:.4rem">
                                    <div class="hb-row" style="gap:.4rem">
                                        <button class="hb-btn hb-btn--success hb-btn--sm" type="submit"
                                                name="estado" value="{{ \App\Models\Reporte::ESTADO_ATENDIDO }}">
                                            Atendido
                                        </button>
                                        <button class="hb-btn hb-btn--ghost hb-btn--sm" type="submit"
                                                name="estado" value="{{ \App\Models\Reporte::ESTADO_DESCARTADO }}">
                                            Descartar
                                        </button>
                                    </div>
                                </form>
                                <a class="hb-btn hb-btn--link hb-btn--sm" style="margin-top:.4rem"
                                   href="{{ route('admin.publicaciones.index', ['q' => $r->alojamiento->title]) }}">
                                    Gestionar la publicación →
                                </a>
                            @else
                                <span class="hb-muted hb-small">{{ $r->revisado_en?->format('d/m/Y') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="hb-center hb-muted" style="padding:2.5rem">
                            No hay reportes en este estado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($reportes->hasPages())
        <div class="hb-card__footer">{{ $reportes->links() }}</div>
    @endif
</div>
@endsection
