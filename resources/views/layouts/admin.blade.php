<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo') · Admin HABBI</title>

    <link rel="icon" href="{{ asset('assets/img/images/Icono.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('assets/css/habbi.css') }}">
</head>
<body>
<div class="hb-admin">

    <aside class="hb-side">
        <div class="hb-side__brand">
            <a href="{{ route('admin.index') }}">
                @include('partials.logo', ['tono' => 'claro'])
            </a>
        </div>

        <ul class="hb-side__nav">
            <li>
                <a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.index') ? 'is-active' : '' }}">
                    <span aria-hidden="true">▦</span> Resumen
                </a>
            </li>
            <li>
                <a href="{{ route('admin.usuarios.index') }}" class="{{ request()->routeIs('admin.usuarios.*') ? 'is-active' : '' }}">
                    <span aria-hidden="true">◍</span> Usuarios
                </a>
            </li>
            <li>
                <a href="{{ route('admin.alojamientos.index') }}" class="{{ request()->routeIs('admin.alojamientos.*') ? 'is-active' : '' }}">
                    <span aria-hidden="true">⌂</span> Alojamientos
                </a>
            </li>
        </ul>

        <div class="hb-side__foot">
            <a href="{{ route('index') }}">← Volver al sitio</a>
        </div>
    </aside>

    <div class="hb-admin__main">
        <header class="hb-admin__head">
            <div>
                <h1>@yield('titulo')</h1>
                @hasSection('subtitulo')
                    <p class="hb-muted hb-small hb-mb-0">@yield('subtitulo')</p>
                @endif
            </div>
            @yield('acciones')
        </header>

        <div class="hb-admin__body">
            @include('partials.flash')
            @yield('contenido')
        </div>
    </div>

</div>
<script src="{{ asset('assets/js/habbi.js') }}" defer></script>
</body>
</html>
