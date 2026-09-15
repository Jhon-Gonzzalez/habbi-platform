@extends('layouts.app')

@section('titulo', $alojamiento->title)
@section('descripcion', Str::limit($alojamiento->description, 150))

@section('contenido')

<nav class="hb-small hb-muted" style="margin-bottom:1.25rem" aria-label="Migas de pan">
    <a href="{{ route('alojamientos.index') }}">Alojamientos</a>
    <span aria-hidden="true">›</span>
    <span>{{ $alojamiento->city }}</span>
</nav>

@unless ($alojamiento->is_active)
    <div class="hb-alert hb-alert--info">
        <span class="hb-alert__icon">i</span>
        <div>Esta publicación está <strong>pausada</strong>: solo tú puedes verla, no aparece en las búsquedas.</div>
    </div>
@endunless

<div class="hb-detail">

    {{-- ===== Columna principal ===== --}}
    <div>
        {{-- Galería --}}
        <div class="hb-gallery">
            <div class="hb-gallery__main">
                <img id="galeriaPrincipal"
                     src="{{ $galeria[0]['url'] ?? $alojamiento->coverUrl() }}"
                     alt="Foto principal de {{ $alojamiento->title }}">
            </div>

            @if (count($galeria) > 1)
                <div class="hb-gallery__thumbs">
                    @foreach ($galeria as $i => $foto)
                        <button type="button" data-galeria-thumb="{{ $foto['url'] }}"
                                class="{{ $i === 0 ? 'is-active' : '' }}"
                                aria-label="Ver foto {{ $i + 1 }}">
                            <img src="{{ $foto['url'] }}" alt="" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Encabezado --}}
        <div style="margin-top:1.75rem">
            <div class="hb-row hb-row--between">
                <span class="hb-badge hb-badge--primary">{{ $alojamiento->type }}</span>
                <x-estrellas :valor="$alojamiento->promedio()" :total="$alojamiento->totalResenas()" grande />
            </div>

            <h1 style="margin-top:.75rem">{{ $alojamiento->title }}</h1>
            <p class="hb-muted">
                {{ $alojamiento->city }}@if ($alojamiento->neighborhood), {{ $alojamiento->neighborhood }}@endif
                @if ($alojamiento->address) · {{ $alojamiento->address }} @endif
            </p>
        </div>

        {{-- Características --}}
        <div class="hb-specs" style="margin:1.5rem 0 2rem">
            <div class="hb-spec">
                <div class="hb-spec__label">Capacidad</div>
                <div class="hb-spec__value">{{ $alojamiento->guests }} @plural('huésped', $alojamiento->guests)</div>
            </div>
            <div class="hb-spec">
                <div class="hb-spec__label">Tipo</div>
                <div class="hb-spec__value">{{ $alojamiento->type }}</div>
            </div>
            <div class="hb-spec">
                <div class="hb-spec__label">Publicado</div>
                <div class="hb-spec__value">{{ $alojamiento->created_at->translatedFormat('d M Y') }}</div>
            </div>
            <div class="hb-spec">
                <div class="hb-spec__label">Reseñas</div>
                <div class="hb-spec__value">{{ $alojamiento->totalResenas() }}</div>
            </div>
        </div>

        {{-- Descripción --}}
        <div class="hb-card">
            <div class="hb-card__body">
                <h2 style="font-size:1.2rem">Sobre este alojamiento</h2>
                <p style="white-space:pre-line;margin-bottom:0">{{ $alojamiento->description }}</p>
            </div>
        </div>

        {{-- Comodidades --}}
        @if (!empty($alojamiento->amenities))
            <div class="hb-card" style="margin-top:1.5rem">
                <div class="hb-card__body">
                    <h2 style="font-size:1.2rem">Qué incluye</h2>
                    <div class="hb-chips">
                        @foreach ($alojamiento->amenities as $comodidad)
                            <span class="hb-chip">{{ $comodidad }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- ===== Reseñas ===== --}}
        <div class="hb-card" style="margin-top:1.5rem" id="resenas">
            <div class="hb-card__header">
                <h2 style="font-size:1.2rem">
                    Reseñas
                    <span class="hb-muted hb-small">({{ $alojamiento->totalResenas() }})</span>
                </h2>
                <x-estrellas :valor="$alojamiento->promedio()" />
            </div>

            {{-- Formulario --}}
            @auth
                @if ($puedeVotar)
                    <div class="hb-card__body" style="border-bottom:1px solid var(--hb-border)">
                        <h3 style="font-size:1rem">
                            {{ $miResena ? 'Actualiza tu reseña' : '¿Ya viviste aquí? Cuéntanos' }}
                        </h3>

                        <form method="POST" action="{{ route('ratings.store', $alojamiento) }}">
                            @csrf

                            <div class="hb-row" style="margin-bottom:1rem">
                                <div class="hb-rate" id="ratingWidget">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <button type="button" aria-label="{{ $i }} @plural('estrella', $i)">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M12 .587l3.668 7.568L24 9.748l-6 5.848L19.335 24 12 19.897 4.665 24 6 15.596l-6-5.848 8.332-1.593z"/>
                                            </svg>
                                        </button>
                                    @endfor
                                </div>
                                <span class="hb-muted hb-small" id="ratingLabel">Selecciona una calificación</span>
                            </div>

                            <input type="hidden" name="rating" id="ratingValue" value="{{ old('rating', $miResena->rating ?? 0) }}">

                            <div class="hb-field">
                                <label class="hb-label hb-sr-only" for="comment">Comentario</label>
                                <textarea class="hb-textarea @error('comment') is-invalid @enderror"
                                          id="comment" name="comment" rows="3"
                                          placeholder="¿Cómo fue tu experiencia? (opcional)">{{ old('comment', $miResena->comment ?? '') }}</textarea>
                                @error('comment') <span class="hb-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="hb-row">
                                <button class="hb-btn hb-btn--primary" type="submit">
                                    {{ $miResena ? 'Actualizar reseña' : 'Publicar reseña' }}
                                </button>

                                @if ($miResena)
                                    <button class="hb-btn hb-btn--link" type="submit"
                                            form="eliminarResena{{ $miResena->id }}">Eliminar mi reseña</button>
                                @endif
                            </div>
                        </form>

                        @if ($miResena)
                            <form id="eliminarResena{{ $miResena->id }}" method="POST"
                                  action="{{ route('ratings.destroy', $miResena) }}"
                                  data-confirmar="¿Eliminar tu reseña?">
                                @csrf @method('DELETE')
                            </form>
                        @endif
                    </div>
                @elseif ($alojamiento->user_id === auth()->id())
                    <div class="hb-card__body" style="border-bottom:1px solid var(--hb-border)">
                        <p class="hb-muted hb-small hb-mb-0">Esta es tu publicación, así que no puedes calificarla.</p>
                    </div>
                @endif
            @else
                <div class="hb-card__body" style="border-bottom:1px solid var(--hb-border)">
                    <p class="hb-mb-0 hb-small">
                        <a href="{{ route('login') }}">Inicia sesión</a> para dejar tu reseña.
                    </p>
                </div>
            @endauth

            {{-- Listado --}}
            <div class="hb-card__body">
                @forelse ($resenas as $resena)
                    <article class="hb-review">
                        <div class="hb-review__avatar">{{ $resena->user->initials() }}</div>
                        <div style="flex:1">
                            <div class="hb-review__head">
                                <span class="hb-review__name">{{ $resena->user->name }}</span>
                                <x-estrellas :valor="$resena->rating" />
                                <span class="hb-review__date">{{ $resena->created_at->diffForHumans() }}</span>
                            </div>
                            @if ($resena->comment)
                                <p>{{ $resena->comment }}</p>
                            @endif
                        </div>

                        @can('delete', $resena)
                            <form method="POST" action="{{ route('ratings.destroy', $resena) }}"
                                  data-confirmar="¿Eliminar esta reseña?">
                                @csrf @method('DELETE')
                                <button class="hb-btn hb-btn--link hb-small" type="submit">Eliminar</button>
                            </form>
                        @endcan
                    </article>
                @empty
                    <p class="hb-muted hb-mb-0">Todavía no hay reseñas. Sé el primero en opinar.</p>
                @endforelse

                @if ($resenas->hasPages())
                    <div class="hb-pagination">{{ $resenas->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== Panel lateral ===== --}}
    <aside class="hb-booking">
        <div class="hb-card">
            <div class="hb-card__body">
                <div class="hb-booking__price">
                    {{ $alojamiento->precioFormateado() }}
                    <small>/ {{ $alojamiento->price_period }}</small>
                </div>
                <p class="hb-muted hb-small">Pesos colombianos (COP)</p>

                <hr style="border:0;border-top:1px solid var(--hb-border);margin:1.25rem 0">

                <div class="hb-row" style="margin-bottom:1.25rem">
                    <div class="hb-user__avatar" style="width:40px;height:40px;font-size:.85rem">
                        {{ $alojamiento->user->initials() }}
                    </div>
                    <div>
                        <div style="font-weight:600">{{ $alojamiento->user->name }}</div>
                        <div class="hb-muted hb-small">Arrendador</div>
                    </div>
                </div>

                @auth
                    @if ($alojamiento->telefonoLimpio())
                        <a class="hb-btn hb-btn--success hb-btn--block" style="margin-bottom:.6rem" target="_blank" rel="noopener"
                           href="https://wa.me/{{ $alojamiento->telefonoLimpio() }}?text={{ urlencode('Hola, vi tu alojamiento "' . $alojamiento->title . '" en HABBI y me interesa.') }}">
                            Contactar por WhatsApp
                        </a>
                        <a class="hb-btn hb-btn--ghost hb-btn--block" href="tel:{{ $alojamiento->telefonoLimpio() }}">
                            Llamar al {{ $alojamiento->phone }}
                        </a>
                    @else
                        <p class="hb-muted hb-small hb-mb-0">Este arrendador no dejó teléfono de contacto.</p>
                    @endif
                @else
                    <a class="hb-btn hb-btn--primary hb-btn--block" href="{{ route('login') }}">
                        Inicia sesión para contactar
                    </a>
                    <p class="hb-muted hb-small hb-center" style="margin:.75rem 0 0">
                        Protegemos los datos de contacto de los arrendadores.
                    </p>
                @endauth

                @can('update', $alojamiento)
                    <hr style="border:0;border-top:1px solid var(--hb-border);margin:1.25rem 0">
                    <a class="hb-btn hb-btn--ghost hb-btn--block" href="{{ route('alojamientos.edit', $alojamiento) }}">
                        Editar publicación
                    </a>
                @endcan
            </div>
        </div>
    </aside>

</div>
@endsection
