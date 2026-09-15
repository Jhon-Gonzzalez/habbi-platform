<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2091F9">
    <meta name="description" content="@yield('descripcion', 'HABBI: encuentra y publica alojamiento estudiantil verificado, con reseñas reales y contacto directo.')">

    <title>@yield('titulo', 'Inicio') · HABBI</title>

    <link rel="icon" href="{{ asset('assets/img/images/Icono.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('assets/css/habbi.css') }}">
    @stack('estilos')
</head>
<body>

    @include('partials.navbar', ['transparente' => $navTransparente ?? false])

    <main id="contenido">
        @hasSection('cabecera')
            <div class="hb-pagehead">
                <div class="hb-container hb-pagehead__inner">
                    @yield('cabecera')
                </div>
            </div>
        @endif

        @hasSection('contenido')
            <div class="hb-container hb-page">
                @include('partials.flash')
                @yield('contenido')
            </div>
        @else
            @yield('contenido_completo')
        @endif
    </main>

    @include('partials.footer')

    <script src="{{ asset('assets/js/habbi.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
