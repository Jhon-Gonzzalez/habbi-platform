{{-- Tarjeta reutilizable de alojamiento. Espera $a (Alojamiento) y opcionalmente $gestion. --}}
@php $gestion = $gestion ?? false; @endphp

<article class="hb-listing">
    <a class="hb-listing__media" href="{{ route('alojamientos.show', $a) }}">
        <img src="{{ $a->coverUrl() }}" alt="Foto de {{ $a->title }}" loading="lazy">
        <span class="hb-listing__price">
            {{ $a->precioFormateado() }} <small>/ {{ $a->price_period }}</small>
        </span>
        {{--
            La etiqueta de destacado va la ÚLTIMA en esta cadena, no la
            primera: si una publicación está pausada o ya arrendada, eso
            es lo que el estudiante necesita saber, haya pagado o no. El
            dinero no tapa el estado real de la publicación.

            Y dice «Destacado» en claro. Hacer pasar un anuncio pagado por
            un resultado normal es engañar al que busca, y es justo lo que
            hace que la gente deje de fiarse de un buscador.
        --}}
        @if ($a->estaPendiente())
            <span class="hb-listing__flag">En revisión</span>
        @elseif ($a->fueRechazada())
            <span class="hb-listing__flag">Rechazada</span>
        @elseif (!$a->is_active)
            <span class="hb-listing__flag">Pausado</span>
        @elseif ($a->estaArrendado())
            <span class="hb-listing__flag">Ya arrendado</span>
        @elseif ($a->estaDestacado())
            <span class="hb-listing__flag hb-listing__flag--destacado" title="Este arrendador pagó para que su anuncio aparezca de primero">
                ★ Destacado
            </span>
        @endif
    </a>

    @auth
        {{-- El corazón va fuera del enlace de la tarjeta: si estuviera
             dentro, al guardar se abriría también el detalle. --}}
        @php($guardado = auth()->user()->tieneEnFavoritos($a))
        <form class="hb-fav" method="POST" action="{{ route('favoritos.toggle', $a) }}">
            @csrf
            <button type="submit"
                    class="hb-fav__btn {{ $guardado ? 'is-on' : '' }}"
                    title="{{ $guardado ? 'Quitar de favoritos' : 'Guardar en favoritos' }}"
                    aria-label="{{ $guardado ? 'Quitar de favoritos' : 'Guardar en favoritos' }}"
                    aria-pressed="{{ $guardado ? 'true' : 'false' }}">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                    <path d="M12 21s-7.5-4.7-9.3-9.1C1.3 8.3 3.2 5 6.6 5c2 0 3.5 1.1 4.4 2.4h2C13.9 6.1 15.4 5 17.4 5c3.4 0 5.3 3.3 3.9 6.9C19.5 16.3 12 21 12 21z"
                          fill="{{ $guardado ? 'currentColor' : 'none' }}"
                          stroke="currentColor" stroke-width="1.8"/>
                </svg>
            </button>
        </form>
    @endauth

    <div class="hb-listing__body">
        <h3 class="hb-listing__title">
            <a href="{{ route('alojamientos.show', $a) }}">{{ $a->title }}</a>
        </h3>

        <x-estrellas :valor="$a->promedio()" :total="$a->totalResenas()" />

        <div class="hb-listing__meta">
            @unless ($a->estaDisponibleYa())
                <span class="hb-badge hb-badge--warning">{{ $a->textoDisponibilidad() }}</span>
            @endunless
            <span>{{ $a->type }}</span>
            <span>{{ $a->guests }} @plural('huésped', $a->guests)</span>
            <span>{{ $a->city }}@if ($a->neighborhood), {{ $a->neighborhood }}@endif</span>
        </div>

        @if (!empty($a->amenities))
            <div class="hb-chips">
                @foreach (array_slice($a->amenities, 0, 3) as $comodidad)
                    <span class="hb-chip">{{ $comodidad }}</span>
                @endforeach
                @if (count($a->amenities) > 3)
                    <span class="hb-chip hb-chip--plain">+{{ count($a->amenities) - 3 }}</span>
                @endif
            </div>
        @endif

        <div class="hb-listing__actions">
            @if ($gestion)
                <a class="hb-btn hb-btn--ghost hb-btn--sm" href="{{ route('alojamientos.edit', $a) }}">Editar</a>

                {{-- Solo se ofrece destacar lo que de verdad se ve en el
                     buscador: cobrar por subir de posición algo que está
                     pendiente o pausado sería cobrar por nada. --}}
                @if (config('destacados.activo') && $a->esVisibleAlPublico())
                    <a class="hb-btn hb-btn--ghost hb-btn--sm" href="{{ route('alojamientos.destacar', $a) }}">
                        {{ $a->estaDestacado() ? '★ Destacada' : 'Destacar' }}
                    </a>
                @endif

                <form method="POST" action="{{ route('alojamientos.toggle', $a) }}" style="flex:1">
                    @csrf @method('PATCH')
                    <button class="hb-btn hb-btn--ghost hb-btn--sm hb-btn--block" type="submit">
                        {{ $a->is_active ? 'Pausar' : 'Publicar' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('alojamientos.destroy', $a) }}" style="flex:1"
                      data-confirmar="¿Seguro que quieres eliminar «{{ $a->title }}»? Esta acción no se puede deshacer.">
                    @csrf @method('DELETE')
                    <button class="hb-btn hb-btn--danger hb-btn--sm hb-btn--block" type="submit">Eliminar</button>
                </form>
            @else
                <a class="hb-btn hb-btn--primary hb-btn--sm" href="{{ route('alojamientos.show', $a) }}">Ver detalle</a>
            @endif
        </div>
    </div>
</article>
