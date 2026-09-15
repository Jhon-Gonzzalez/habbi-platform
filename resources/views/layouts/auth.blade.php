<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo') · HABBI</title>

    <link rel="icon" href="{{ asset('assets/img/images/Icono.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('assets/css/habbi.css') }}">
</head>
<body>
<div class="hb-auth">

    <aside class="hb-auth__aside">
        @include('partials.logo', ['tono' => 'claro'])
        <h2>Tu próximo hogar de estudiante está a un clic</h2>
        <p>Miles de habitaciones, apartaestudios y apartamentos cerca de tu universidad.</p>

        <ul class="hb-auth__points">
            <li><span>✓</span> Reseñas verificadas de otros estudiantes</li>
            <li><span>✓</span> Filtros por precio, barrio y comodidades</li>
            <li><span>✓</span> Contacto directo con el arrendador</li>
            <li><span>✓</span> Publica tu alojamiento gratis</li>
        </ul>
    </aside>

    <section class="hb-auth__main">
        <div class="hb-auth__form">
            <a class="hb-auth__logo" href="{{ route('index') }}">
                @include('partials.logo')
            </a>

            @include('partials.flash')

            @yield('formulario')
        </div>
    </section>

</div>
<script src="{{ asset('assets/js/habbi.js') }}" defer></script>
</body>
</html>
