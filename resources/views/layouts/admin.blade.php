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
            <li>
                @php $pendientes = \App\Models\SolicitudArrendador::pendientes()->count(); @endphp
                <a href="{{ route('admin.solicitudes.index') }}" class="{{ request()->routeIs('admin.solicitudes.*') ? 'is-active' : '' }}">
                    <span aria-hidden="true">✓</span> Solicitudes
                    @if ($pendientes)
                        <span class="hb-badge hb-badge--danger" style="margin-left:auto">{{ $pendientes }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ route('admin.informes.index') }}" class="{{ request()->routeIs('admin.informes.*') ? 'is-active' : '' }}">
                    <span aria-hidden="true">▤</span> Informes
                </a>
            </li>
            <li>
                @php $enRevision = \App\Models\Alojamiento::pendientes()->count(); @endphp
                <a href="{{ route('admin.revision.index') }}" class="{{ request()->routeIs('admin.revision.*') ? 'is-active' : '' }}">
                    <span aria-hidden="true">◎</span> Revisión
                    @if ($enRevision)
                        <span class="hb-badge hb-badge--danger" style="margin-left:auto">{{ $enRevision }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ route('admin.aliados.index') }}" class="{{ request()->routeIs('admin.aliados.*') ? 'is-active' : '' }}">
                    <span aria-hidden="true">◈</span> Aliados
                </a>
            </li>
            <li>
                @php $enCampana = \App\Models\Alojamiento::conCampana()->count(); @endphp
                <a href="{{ route('admin.campanas.index') }}" class="{{ request()->routeIs('admin.campanas.*') ? 'is-active' : '' }}">
                    <span aria-hidden="true">★</span> Campañas
                    @if ($enCampana)
                        <span class="hb-badge hb-badge--success" style="margin-left:auto">{{ $enCampana }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ route('admin.tarifas.index') }}" class="{{ request()->routeIs('admin.tarifas.*') ? 'is-active' : '' }}">
                    <span aria-hidden="true">$</span> Tarifas y cobro
                </a>
            </li>
            <li>
                @php $reportesAbiertos = \App\Models\Reporte::abiertos()->count(); @endphp
                <a href="{{ route('admin.reportes.index') }}" class="{{ request()->routeIs('admin.reportes.*') ? 'is-active' : '' }}">
                    <span aria-hidden="true">⚑</span> Reportes
                    @if ($reportesAbiertos)
                        <span class="hb-badge hb-badge--danger" style="margin-left:auto">{{ $reportesAbiertos }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ route('admin.auditoria.index') }}" class="{{ request()->routeIs('admin.auditoria.*') ? 'is-active' : '' }}">
                    <span aria-hidden="true">≡</span> Auditoría
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
