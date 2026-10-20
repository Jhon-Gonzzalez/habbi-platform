{{--
    Mapa interactivo con pines (RF-6, CU-04).

    Espera $puntos: una colección de arrays con lat, lng, titulo, precio y
    url. Si viene vacía, no se pinta nada: un mapa sin pines solo confunde.

    Arranca en tamaño compacto. A pantalla completa ocupaba casi todo lo
    que se ve y empujaba los resultados fuera de la pantalla, que es justo
    al revés de lo que hace falta: el mapa acompaña al listado. Los dos
    botones dejan ampliarlo o quitarlo de en medio, y la elección se
    recuerda en el navegador para no tener que repetirla en cada búsqueda.

    Leaflet y los mapas de OpenStreetMap son libres y no piden clave de
    API. A cambio hay que dejar la atribución visible, que Leaflet pone
    sola.
--}}

@php($puntos = collect($puntos ?? [])->filter(fn ($p) => isset($p['lat'], $p['lng']))->values())

@if ($puntos->isNotEmpty())
    @include('partials.leaflet')

    <div class="hb-mapa-caja" data-mapa>
        <div class="hb-mapa-barra">
            <span class="hb-mapa-barra__titulo">
                <span aria-hidden="true">📍</span>
                {{ $puntos->count() }} @plural('alojamiento', $puntos->count()) en el mapa
            </span>

            <span class="hb-mapa-barra__botones">
                <button class="hb-btn hb-btn--ghost hb-btn--sm" type="button"
                        data-mapa-tamano aria-pressed="false">Ampliar</button>
                <button class="hb-btn hb-btn--ghost hb-btn--sm" type="button"
                        data-mapa-ver aria-expanded="true">Ocultar</button>
            </span>
        </div>

        <div id="{{ $id ?? 'mapa' }}" class="hb-mapa-lienzo"
             data-puntos="{{ json_encode($puntos, JSON_UNESCAPED_UNICODE) }}"
             data-universidades="{{ json_encode(\App\Models\Alojamiento::universidadesConCoordenadas(), JSON_UNESCAPED_UNICODE) }}"></div>
    </div>

    @push('scripts')
        <script src="{{ asset('assets/js/mapa.js') }}" defer></script>
    @endpush
@endif
