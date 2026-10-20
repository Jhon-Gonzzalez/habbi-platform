@extends('layouts.admin')

@section('titulo', 'Revisión de publicaciones')
@section('subtitulo', $pendientes . ' ' . \App\Support\Texto::plural('publicación', $pendientes) . ' esperando')

@section('contenido')
<div class="hb-card">
    <div class="hb-card__header">
        <div class="hb-row">
            @foreach (\App\Models\Alojamiento::ESTADOS as $clave => $texto)
                <a class="hb-btn hb-btn--sm {{ $estado === $clave ? 'hb-btn--primary' : 'hb-btn--ghost' }}"
                   href="{{ route('admin.revision.index', ['estado' => $clave]) }}">
                    {{ $texto }}
                    @if ($clave === \App\Models\Alojamiento::PENDIENTE && $pendientes)
                        <span class="hb-badge hb-badge--danger">{{ $pendientes }}</span>
                    @endif
                </a>
            @endforeach
            <a class="hb-btn hb-btn--sm {{ ! array_key_exists($estado, \App\Models\Alojamiento::ESTADOS) ? 'hb-btn--primary' : 'hb-btn--ghost' }}"
               href="{{ route('admin.revision.index', ['estado' => 'todas']) }}">Todas</a>
        </div>
    </div>

    <div class="hb-table-wrap">
        <table class="hb-table">
            <thead>
                <tr><th></th><th>Publicación</th><th>Arrendador</th><th>Enviada</th><th>Estado</th><th></th></tr>
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
                        </td>
                        <td>
                            <a class="hb-btn hb-btn--ghost hb-btn--sm"
                               href="{{ route('admin.revision.show', $a) }}">Revisar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="hb-center hb-muted" style="padding:2.5rem">
                        No hay publicaciones en este estado.
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
