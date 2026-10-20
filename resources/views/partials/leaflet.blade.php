{{--
    Carga de Leaflet, en un solo sitio.

    Lo usan el mapa de resultados y el selector de coordenadas del
    formulario. Hoy no coinciden en la misma página, pero @once evita que
    se cargue dos veces el día que coincidan.

    Versión fija y con integrity: si el CDN sirviera otro archivo, el
    navegador lo rechaza en vez de ejecutarlo.
--}}
@once
    @push('estilos')
        <link rel="stylesheet"
              href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"
              integrity="sha512-h9FcoyWjHcOcmEVkxOfTLnmZFWIH0iZhZT1H2TbOq55xssQGEJHEaIm+PgoUaZbRvQTNTluNOEfb1ZRy6D3BOw=="
              crossorigin="anonymous" referrerpolicy="no-referrer">
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"
                integrity="sha512-BwHfrr4c9kmRkLw6iXFdzcdWV/PGkVgiIyIWLLlTSXzWQzxuSg4DiQUCpauz/EWjgk5TYQqX/kvn9pG1NpYfqg=="
                crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    @endpush
@endonce
