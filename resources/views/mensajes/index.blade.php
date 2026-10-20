@extends('layouts.app')

@section('titulo', 'Mensajes')

@section('cabecera')
    <div>
        <h1>Mensajes</h1>
        <p>Tus conversaciones con arrendadores y estudiantes.</p>
    </div>
@endsection

@section('contenido')

@if ($conversaciones->isEmpty())
    <div class="hb-empty">
        <div class="hb-empty__icon" aria-hidden="true">✉</div>
        <h3>Todavía no tienes conversaciones</h3>
        <p>
            Cuando escribas a un arrendador desde la ficha de un alojamiento,
            la conversación aparecerá aquí.
        </p>
        <p style="margin-top:1.25rem">
            <a class="hb-btn hb-btn--primary" href="{{ route('alojamientos.index') }}">Buscar alojamiento</a>
        </p>
    </div>
@else
    <div class="hb-card">
        <ul class="hb-hilos">
            @foreach ($conversaciones as $c)
                @php($otro = $c->otro(auth()->user()))
                @php($sinLeer = $c->sinLeerPara(auth()->user()))
                <li class="hb-hilo {{ $sinLeer ? 'is-nuevo' : '' }}">
                    <a href="{{ route('mensajes.show', $c) }}">
                        <x-avatar :usuario="$otro" tamano="44" />

                        <div class="hb-hilo__texto">
                            <div class="hb-row hb-row--between">
                                <strong>{{ $otro?->name ?? 'Cuenta eliminada' }}</strong>
                                <span class="hb-muted hb-small">
                                    {{ $c->ultimo_mensaje_en?->diffForHumans() ?? 'sin mensajes' }}
                                </span>
                            </div>
                            <div class="hb-muted hb-small">{{ Str::limit($c->alojamiento->title, 60) }}</div>

                            @if ($c->acuerdoConfirmado())
                                <span class="hb-badge hb-badge--success">Acuerdo cerrado</span>
                            @elseif ($c->acuerdoPropuesto())
                                <span class="hb-badge hb-badge--warning">Acuerdo por confirmar</span>
                            @endif
                        </div>

                        @if ($sinLeer)
                            <span class="hb-badge hb-badge--danger">{{ $sinLeer }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($conversaciones->hasPages())
            <div class="hb-card__footer">{{ $conversaciones->links() }}</div>
        @endif
    </div>
@endif

@endsection
