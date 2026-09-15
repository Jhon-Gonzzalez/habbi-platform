<footer class="hb-footer">
    <div class="hb-container">
        <div class="hb-footer__grid">
            <div>
                <div class="hb-footer__logo">
                    @include('partials.logo', ['tono' => 'claro'])
                </div>
                <p>Alojamiento estudiantil verificado, con reseñas reales y contacto directo con el arrendador.</p>
            </div>

            <div>
                <h4>Plataforma</h4>
                <ul>
                    <li><a href="{{ route('alojamientos.index') }}">Buscar alojamiento</a></li>
                    <li><a href="{{ route('alojamientos.create') }}">Publicar alojamiento</a></li>
                    <li><a href="{{ route('index') }}#como-funciona">Cómo funciona</a></li>
                </ul>
            </div>

            <div>
                <h4>Cuenta</h4>
                <ul>
                    @auth
                        <li><a href="{{ route('home') }}">Mi cuenta</a></li>
                        <li><a href="{{ route('alojamientos.mine') }}">Mis publicaciones</a></li>
                    @else
                        <li><a href="{{ route('login') }}">Iniciar sesión</a></li>
                        <li><a href="{{ route('register') }}">Crear cuenta</a></li>
                    @endauth
                </ul>
            </div>

            <div>
                <h4>Contacto</h4>
                <ul>
                    <li><a href="mailto:hola@eliandval.com">hola@eliandval.com</a></li>
                    <li>Colombia</li>
                </ul>
            </div>
        </div>

        <div class="hb-footer__bottom">
            <span>&copy; {{ date('Y') }} HABBI. Todos los derechos reservados.</span>
            <span>Hecho con Laravel</span>
        </div>
    </div>
</footer>
