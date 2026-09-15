@php $transparente = $transparente ?? false; @endphp

<header class="hb-nav {{ $transparente ? 'hb-nav--transparent' : '' }}">
    <nav class="hb-container hb-nav__inner" aria-label="Navegación principal">

        <a class="hb-nav__brand" href="{{ route('index') }}" aria-label="Inicio de HABBI">
            @include('partials.logo', ['tono' => $transparente ? 'claro' : 'oscuro'])
        </a>

        <button class="hb-nav__toggle" id="navToggle" type="button"
                aria-expanded="false" aria-controls="navLinks" aria-label="Abrir menú">
            <span></span><span></span><span></span>
        </button>

        <ul class="hb-nav__links" id="navLinks">
            <li>
                <a class="hb-nav__link {{ request()->routeIs('index') ? 'is-active' : '' }}"
                   href="{{ route('index') }}">Inicio</a>
            </li>
            <li>
                <a class="hb-nav__link {{ request()->routeIs('alojamientos.index') ? 'is-active' : '' }}"
                   href="{{ route('alojamientos.index') }}">Buscar</a>
            </li>
            <li>
                <a class="hb-nav__link {{ request()->routeIs('alojamientos.create') ? 'is-active' : '' }}"
                   href="{{ route('alojamientos.create') }}">Publicar</a>
            </li>

            @auth
                <li class="hb-user">
                    <button class="hb-user__btn" id="userMenuBtn" type="button"
                            aria-expanded="false" aria-haspopup="true">
                        <span class="hb-user__avatar">{{ auth()->user()->initials() }}</span>
                        {{ Str::before(auth()->user()->name, ' ') }}
                        <svg width="12" height="12" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M7 10l5 5 5-5z"/>
                        </svg>
                    </button>

                    <ul class="hb-user__menu" id="userMenu">
                        <li class="hb-user__head">
                            <strong>{{ auth()->user()->name }}</strong>
                            <span>{{ auth()->user()->email }}</span>
                        </li>
                        <li><a href="{{ route('home') }}">Mi cuenta</a></li>
                        <li><a href="{{ route('alojamientos.mine') }}">Mis publicaciones</a></li>
                        <li><a href="{{ route('alojamientos.create') }}">Publicar alojamiento</a></li>

                        @if (auth()->user()->isAdmin())
                            <li><hr></li>
                            <li><a href="{{ route('admin.index') }}">Panel de administración</a></li>
                        @endif

                        <li><hr></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="is-danger">Cerrar sesión</button>
                            </form>
                        </li>
                    </ul>
                </li>
            @else
                <li>
                    <a class="hb-nav__link {{ request()->routeIs('login') ? 'is-active' : '' }}"
                       href="{{ route('login') }}">Iniciar sesión</a>
                </li>
                <li>
                    <a class="hb-btn hb-btn--primary hb-btn--sm" href="{{ route('register') }}">Crear cuenta</a>
                </li>
            @endauth
        </ul>
    </nav>
</header>
