{{--
    Negocios aliados: publicidad local, vendida directamente.

    Reglas de colocación, y son deliberadas:

    · NUNCA dentro de la rejilla de resultados. Un anuncio con forma de
      tarjeta de alojamiento es un engaño, y en una página de vivienda es
      además un vector de estafa: alguien pagaría por parecer un cuarto.
    · Va rotulado «Publicidad» en claro, no como «recomendados» ni
      «sugerencias». Si hay que disimular lo que es, no debería estar.
    · Nunca en el chat ni en la ficha del alojamiento. Ahí la persona está
      decidiendo dónde va a vivir; interrumpir eso para vender un almuerzo
      es ganar mil pesos y perder la confianza.
    · Los enlaces llevan rel="sponsored nofollow noopener": es lo que
      corresponde a un enlace pagado y lo que evita que el buscador lo
      lea como una recomendación nuestra.

    No hay script de terceros ni cookies de rastreo. Los anuncios salen
    de la base de datos de este servidor, así que nadie de fuera se
    entera de quién entró a buscar habitación.
--}}

@php($aliados = \App\Models\Aliado::vigentes()->get())

@if ($aliados->isNotEmpty())
    <aside class="hb-aliados" aria-label="Publicidad de negocios locales">
        <div class="hb-aliados__titulo">
            <span>Publicidad</span>
            <span class="hb-muted hb-small">Negocios cerca de la universidad</span>
        </div>

        <ul class="hb-aliados__lista">
            @foreach ($aliados as $aliado)
                <li>
                    <a class="hb-aliado" href="{{ route('aliados.salir', $aliado) }}"
                       target="_blank" rel="sponsored nofollow noopener noreferrer">
                        @if ($aliado->logoUrl())
                            <img class="hb-aliado__logo" src="{{ $aliado->logoUrl() }}"
                                 alt="Logo de {{ $aliado->nombre }}" loading="lazy" width="44" height="44">
                        @else
                            <span class="hb-aliado__logo hb-aliado__logo--texto" aria-hidden="true">
                                {{ $aliado->iniciales() }}
                            </span>
                        @endif

                        <span class="hb-aliado__texto">
                            <strong>{{ $aliado->nombre }}</strong>
                            <span class="hb-muted hb-small">{{ $aliado->descripcion }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        <p class="hb-hint hb-mb-0">
            Son anuncios pagados. HABBI no responde por lo que ofrezcan ni los ha probado.
        </p>
    </aside>
@endif
