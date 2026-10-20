{{--
    Carga de Leaflet, en un solo sitio.

    Lo usan el mapa de resultados, el de la ficha del alojamiento y el
    selector de coordenadas del formulario. @once evita que se cargue dos
    veces si dos de ellos coinciden en la misma página.

    Se sirve desde el propio proyecto, NO desde un CDN.

    La primera versión lo cargaba de cdnjs con un hash «integrity». Ese
    hash estaba mal, así que el navegador rechazaba el archivo y el mapa
    salía como un recuadro vacío: un fallo silencioso, sin error visible
    en la página. Con el archivo dentro del repositorio no hay hash que
    equivocar, no hay dominio de terceros que pueda caerse o bloquearse,
    y encaja con la decisión del proyecto de no tener paso de compilación
    en el frontend.

    Leaflet 1.9.4, descargado del paquete oficial de npm y comprobado
    contra el sha512 que publica el registro. Licencia BSD de 2
    cláusulas, en vendor/leaflet/LICENSE.
--}}
@once
    @push('estilos')
        <link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/vendor/leaflet/leaflet.js') }}"></script>
    @endpush
@endonce
