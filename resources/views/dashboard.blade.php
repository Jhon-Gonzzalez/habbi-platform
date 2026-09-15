@extends('layouts.app')

@section('titulo', 'Mi cuenta')

@section('cabecera')
    <div>
        <h1>Hola, {{ Str::before($usuario->name, ' ') }}</h1>
        <p>Este es el resumen de tu actividad en HABBI.</p>
    </div>
    <a class="hb-btn hb-btn--white" href="{{ route('alojamientos.create') }}">Publicar alojamiento</a>
@endsection

@section('contenido')

    <div class="hb-stats">
        <div class="hb-stat">
            <div class="hb-stat__icon" aria-hidden="true">⌂</div>
            <div>
                <div class="hb-stat__value">{{ $totalActivos }}</div>
                <div class="hb-stat__label">@plural('Publicación activa', $totalActivos)</div>
            </div>
        </div>

        <div class="hb-stat">
            <div class="hb-stat__icon" aria-hidden="true">⏸</div>
            <div>
                <div class="hb-stat__value">{{ $totalPausados }}</div>
                <div class="hb-stat__label">@plural('Publicación pausada', $totalPausados)</div>
            </div>
        </div>

        <div class="hb-stat">
            <div class="hb-stat__icon" aria-hidden="true">★</div>
            <div>
                <div class="hb-stat__value">{{ $totalResenas }}</div>
                <div class="hb-stat__label">@plural('Reseña escrita', $totalResenas)</div>
            </div>
        </div>
    </div>

    {{-- Últimas publicaciones --}}
    <section style="margin-top:2.5rem">
        <div class="hb-row hb-row--between" style="margin-bottom:1.25rem">
            <h2 style="font-size:1.25rem;margin:0">Tus publicaciones recientes</h2>
            @if ($alojamientos->isNotEmpty())
                <a class="hb-btn hb-btn--link" href="{{ route('alojamientos.mine') }}">Ver todas</a>
            @endif
        </div>

        @if ($alojamientos->isEmpty())
            <div class="hb-empty">
                <div class="hb-empty__icon" aria-hidden="true">⌂</div>
                <h3>Aún no tienes publicaciones</h3>
                <p>Cuando publiques un alojamiento aparecerá aquí con sus reseñas y su estado.</p>
                <p style="margin-top:1.5rem">
                    <a class="hb-btn hb-btn--primary" href="{{ route('alojamientos.create') }}">Publicar ahora</a>
                </p>
            </div>
        @else
            <div class="hb-grid">
                @foreach ($alojamientos as $a)
                    @include('partials.listing-card', ['a' => $a, 'gestion' => true])
                @endforeach
            </div>
        @endif
    </section>

    {{-- Reseñas escritas --}}
    @if ($misResenas->isNotEmpty())
        <section style="margin-top:2.5rem">
            <h2 style="font-size:1.25rem;margin-bottom:1.25rem">Reseñas que has escrito</h2>

            <div class="hb-card">
                <div class="hb-card__body">
                    @foreach ($misResenas as $resena)
                        <article class="hb-review">
                            <img class="hb-table__thumb"
                                 src="{{ $resena->alojamiento?->coverUrl() ?? asset('assets/img/images/principal.jpg') }}" alt="">
                            <div style="flex:1">
                                <div class="hb-review__head">
                                    <a class="hb-review__name" href="{{ route('alojamientos.show', $resena->alojamiento_id) }}">
                                        {{ $resena->alojamiento?->title ?? 'Publicación eliminada' }}
                                    </a>
                                    <x-estrellas :valor="$resena->rating" />
                                    <span class="hb-review__date">{{ $resena->created_at->diffForHumans() }}</span>
                                </div>
                                @if ($resena->comment)
                                    <p>{{ $resena->comment }}</p>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('ratings.destroy', $resena) }}"
                                  data-confirmar="¿Eliminar esta reseña?">
                                @csrf @method('DELETE')
                                <button class="hb-btn hb-btn--link hb-small" type="submit">Eliminar</button>
                            </form>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

@endsection
