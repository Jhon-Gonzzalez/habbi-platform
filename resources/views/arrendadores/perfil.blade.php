@extends('layouts.app')

@section('titulo', $arrendador->name)
@section('descripcion', 'Perfil de ' . $arrendador->name . ' en HABBI' . ($arrendador->ciudad ? ', ' . $arrendador->ciudad : '') . '.')
@section('imagen', $arrendador->fotoUrl() ?? asset('assets/img/images/principal.jpg'))

@section('contenido')

{{-- Ficha del arrendador --}}
<div class="hb-card">
    <div class="hb-card__body">
        <div class="hb-perfil">
            <x-avatar :usuario="$arrendador" :tamano="110" />

            <div>
                <h1 class="hb-perfil__nombre" style="font-size:1.8rem">
                    {{ $arrendador->name }}
                    <x-sello-verificado :usuario="$arrendador" />
                </h1>

                <div class="hb-perfil__meta">
                    <span>{{ $arrendador->nombreRol() }}</span>
                    @if ($arrendador->ciudad)<span>{{ $arrendador->ciudad }}</span>@endif
                    <span>En HABBI desde {{ $arrendador->created_at->translatedFormat('F \d\e Y') }}</span>
                </div>

                @if ($arrendador->bio)
                    <p class="hb-perfil__bio">{{ $arrendador->bio }}</p>
                @endif

                <div class="hb-row" style="margin-top:1.25rem">
                    <x-estrellas :valor="$promedio" :total="$totalResenas" grande />
                    <span class="hb-muted hb-small">
                        · @plural('publicación', $alojamientos->count()) {{ $alojamientos->count() }}
                    </span>
                </div>

                {{-- El teléfono solo con sesión iniciada --}}
                @auth
                    @if ($arrendador->phone)
                        <div class="hb-row" style="margin-top:1.25rem">
                            <a class="hb-btn hb-btn--success hb-btn--sm" target="_blank" rel="noopener"
                               href="https://wa.me/{{ preg_replace('/\D/', '', $arrendador->phone) }}">
                                WhatsApp
                            </a>
                            <a class="hb-btn hb-btn--ghost hb-btn--sm"
                               href="tel:{{ preg_replace('/\D/', '', $arrendador->phone) }}">
                                {{ $arrendador->phone }}
                            </a>
                        </div>
                    @endif
                @else
                    <p class="hb-small hb-muted" style="margin-top:1.25rem">
                        <a href="{{ route('login') }}">Inicia sesión</a> para ver sus datos de contacto.
                    </p>
                @endauth
            </div>
        </div>
    </div>

    {{-- Qué significa el sello --}}
    @if ($arrendador->estaVerificado())
        <div class="hb-card__footer">
            <p class="hb-small hb-mb-0">
                <strong class="hb-verificado">
                    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true">
                        <path fill="currentColor" d="M12 2 9.8 4.2 6.7 3.9 6 7 2.9 7.7 3.9 10.7 2 13l2.3 2.2-.4 3.1 3.1.7.7 3.1 3-1 2.3 1.9 2.2-1.9 3.1.4.7-3.1 3.1-.7-1-3L22 13l-1.9-2.3.4-3.1-3.1-.7-.7-3.1-3 1z"/>
                        <path fill="#fff" d="m10.9 15.4-2.8-2.8 1.1-1.1 1.7 1.7 4-4 1.1 1.1z"/>
                    </svg>
                    Qué comprobamos
                </strong>
                HABBI revisó su documento de identidad y una prueba de que el inmueble
                existe. Aun así, <strong>visita el lugar antes de pagar nada</strong>:
                la verificación confirma quién es, no el estado de la vivienda.
            </p>
        </div>
    @endif
</div>

{{-- Sus publicaciones --}}
<section style="margin-top:2.5rem">
    <h2 style="font-size:1.25rem;margin-bottom:1.25rem">
        @plural('Publicación', $alojamientos->count()) de {{ \Illuminate\Support\Str::before($arrendador->name, ' ') }}
    </h2>

    @if ($alojamientos->isEmpty())
        <div class="hb-empty">
            <div class="hb-empty__icon" aria-hidden="true">⌂</div>
            <h3>No tiene publicaciones activas</h3>
            <p>Puede que las haya pausado temporalmente.</p>
        </div>
    @else
        <div class="hb-grid">
            @foreach ($alojamientos as $a)
                @include('partials.listing-card', ['a' => $a])
            @endforeach
        </div>
    @endif
</section>

@endsection
