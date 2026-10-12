@extends('layouts.admin')

@section('titulo', 'Solicitudes de arrendador')
@section('subtitulo', $totalPendientes . ' ' . \App\Support\Texto::plural('pendiente', $totalPendientes) . ' de revisión')

@section('contenido')
<div class="hb-card">
    <div class="hb-card__header">
        <div class="hb-row">
            @foreach (['pendiente' => 'Pendientes', 'aprobada' => 'Aprobadas', 'rechazada' => 'Rechazadas', 'todas' => 'Todas'] as $clave => $texto)
                <a class="hb-btn hb-btn--sm {{ $estado === $clave ? 'hb-btn--primary' : 'hb-btn--ghost' }}"
                   href="{{ route('admin.solicitudes.index', ['estado' => $clave]) }}">
                    {{ $texto }}
                    @if ($clave === 'pendiente' && $totalPendientes)
                        <span class="hb-badge hb-badge--danger">{{ $totalPendientes }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    <div class="hb-table-wrap">
        <table class="hb-table">
            <thead>
                <tr><th>Solicitante</th><th>Enviada</th><th>Teléfono</th><th>Estado</th><th>Revisó</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($solicitudes as $s)
                    <tr>
                        <td>
                            <div class="hb-table__user">
                                <span class="hb-table__avatar">{{ $s->user->initials() }}</span>
                                <div>
                                    <strong>{{ $s->user->name }}</strong>
                                    <div class="hb-muted hb-small">
                                        {{ \App\Support\Texto::ocultarCorreo($s->user->email) }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="hb-muted hb-small">{{ $s->created_at->diffForHumans() }}</td>
                        <td class="hb-small">{{ $s->telefono }}</td>
                        <td>
                            <span class="hb-badge {{ $s->estaPendiente() ? 'hb-badge--primary' : ($s->fueAprobada() ? 'hb-badge--success' : 'hb-badge--danger') }}">
                                {{ $s->nombreEstado() }}
                            </span>
                        </td>
                        <td class="hb-muted hb-small">{{ $s->revisor?->name ?? '—' }}</td>
                        <td>
                            <div class="hb-table__actions">
                                <a class="hb-btn hb-btn--ghost hb-btn--sm" href="{{ route('admin.solicitudes.show', $s) }}">
                                    {{ $s->estaPendiente() ? 'Revisar' : 'Ver' }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="hb-muted hb-center" style="padding:2.5rem">No hay solicitudes con ese estado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="hb-pagination">{{ $solicitudes->links() }}</div>
@endsection
