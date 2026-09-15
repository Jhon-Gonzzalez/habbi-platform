{{-- Tarjeta reutilizable de alojamiento. Espera $a (Alojamiento) y opcionalmente $gestion. --}}
@php $gestion = $gestion ?? false; @endphp

<article class="hb-listing">
    <a class="hb-listing__media" href="{{ route('alojamientos.show', $a) }}">
        <img src="{{ $a->coverUrl() }}" alt="Foto de {{ $a->title }}" loading="lazy">
        <span class="hb-listing__price">
            {{ $a->precioFormateado() }} <small>/ {{ $a->price_period }}</small>
        </span>
        @if (!$a->is_active)
            <span class="hb-listing__flag">Pausado</span>
        @endif
    </a>

    <div class="hb-listing__body">
        <h3 class="hb-listing__title">
            <a href="{{ route('alojamientos.show', $a) }}">{{ $a->title }}</a>
        </h3>

        <x-estrellas :valor="$a->promedio()" :total="$a->totalResenas()" />

        <div class="hb-listing__meta">
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
