{{--
    Mapa interactivo con pines (RF-6, CU-04).

    Espera $puntos: una colección de arrays con lat, lng, titulo, precio y
    url. Si viene vacía, no se pinta nada: un mapa sin pines solo confunde.

    Leaflet y los mapas de OpenStreetMap son libres y no piden clave de API.
    Es lo que permite que esto funcione sin contratar nada; a cambio, hay
    que dejar la atribución visible, que Leaflet pone sola.
--}}

@php($puntos = collect($puntos ?? [])->filter(fn ($p) => isset($p['lat'], $p['lng']))->values())

@if ($puntos->isNotEmpty())
    @include('partials.leaflet')

    <div class="hb-mapa-caja">
        <div id="{{ $id ?? 'mapa' }}" class="hb-mapa-lienzo"
             data-puntos="{{ json_encode($puntos, JSON_UNESCAPED_UNICODE) }}"
             data-universidades="{{ json_encode(\App\Models\Alojamiento::universidadesConCoordenadas(), JSON_UNESCAPED_UNICODE) }}"></div>
    </div>

    @push('scripts')
        <script src="{{ asset('assets/js/mapa.js') }}" defer></script>
    @endpush
@endif
