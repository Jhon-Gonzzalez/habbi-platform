<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2091F9">
    <meta name="description" content="@yield('descripcion', 'HABBI: encuentra y publica alojamiento estudiantil verificado, con reseñas reales y contacto directo.')">

    <title>@yield('titulo', 'Inicio') · HABBI</title>

    {{-- Vista previa al compartir el enlace.

         En Colombia los enlaces se mandan por WhatsApp, y sin esto llega
         la URL pelada, sin foto ni título. Las etiquetas og: las leen
         WhatsApp, Facebook, Telegram y LinkedIn; las twitter: las usa X.

         La imagen tiene que ser PNG o JPEG: ninguno de ellos previsualiza
         SVG, y tiene que ser una URL absoluta. --}}
    @php
        $hbTitulo = trim($__env->yieldContent('titulo', 'Inicio')) . ' · HABBI';
        $hbDesc   = trim($__env->yieldContent('descripcion',
            'HABBI: encuentra y publica alojamiento estudiantil verificado, con reseñas reales y contacto directo.'));
        $hbImagen = trim($__env->yieldContent('imagen', asset('assets/img/images/principal.jpg')));
    @endphp

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="HABBI">
    <meta property="og:locale" content="es_CO">
    <meta property="og:title" content="{{ $hbTitulo }}">
    <meta property="og:description" content="{{ $hbDesc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $hbImagen }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $hbTitulo }}">
    <meta name="twitter:description" content="{{ $hbDesc }}">
    <meta name="twitter:image" content="{{ $hbImagen }}">

    <link rel="icon" href="{{ asset('assets/img/images/Icono.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('assets/css/habbi.css') }}">
    @stack('estilos')
</head>
<body>

    @include('partials.navbar', ['transparente' => $navTransparente ?? false])

    @include('partials.aviso-verificar')

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
