

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HABBI | Vivienda estudiantil</title>
  
    <link rel="stylesheet" href="{{ asset('assets/img/images/Icono.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/estilos.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/normalize.css') }}">
    <meta name="theme-color" content="#2091F9">
</head>

<body>

    <header class="hero">
        <nav class="nav container">
            <a class="brand" href="index.html" aria-label="Ir a página principal">
                
                 <img src= "{{ asset('assets/img/images/Logo-blanco.png') }}"  alt="Habbi-web" class="brand-logo">
            </a>
            <ul class="nav__link nav__link--menu">
  <li class="nav__items">
    <a href="{{ route('home') }}" class="nav__links">Inicio</a>
  </li>

  <li class="nav__items">
    <a href="#como-funciona" class="nav__links">Cómo funciona</a>
  </li>

  <li class="nav__items">
    <a href="{{ route('publicar') }}" class="nav__links">Publicar</a>
  </li>

  <li class="nav__items">
    <a href="{{ route('alojamiento.index') }}" class="nav__links">Buscar</a>
  </li>

  {{-- === Usuario === --}}
  @auth
    <li class="nav__items user-menu">
      <button class="nav__links user-btn" id="userMenuBtn" type="button">
         {{ Auth::user()->name }}
        <svg width="12" height="12" viewBox="0 0 24 24" style="margin-left:6px">
          <path fill="currentColor" d="M7 10l5 5 5-5z"/>
        </svg>
      </button>

      <ul class="user-dropdown" id="userDropdown" aria-label="Menú de usuario">
        <li><a class="user-item" href="{{ route('home') }}">Mi perfil</a></li>
        <li><a class="user-item" href="{{ route('alojamiento.index') }}">Mis alojamientos</a></li>
        <li>
          <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="user-item user-logout">Cerrar sesión</button>
          </form>
        </li>
      </ul>
    </li>
  @else
    <li class="nav__items">
      <a href="{{ route('login') }}" class="nav__links">Iniciar sesión</a>
    </li>
  @endauth
</ul>


            <div class="nav__menu">
                <img src="{{ asset('assets/img/images/menu.svg') }}"  class="nav__img" alt="Abrir menú">
                
            </div>
        </nav>

        <section class="hero__container container">
            <h1 class="hero__title">Encuentra tu alojamiento<br> ideal en tu ciudad</h1>
            <p class="hero__paragraph">  Explora alojamientos verificados: filtra por precio, servicios y distancia, revisa opiniones de estudiantes y contacta a arrendadores de forma rápida y segura</p>
            <a href="{{ route('alojamiento.index') }}" class="cta">Buscar alojamientos</a>
        </section>
    </header>

    <main>
        <section class="container about">
            <h2 class="subtitle">¿Qué ofrece HABBI?</h2>
            <p class="about__paragraph">Todo lo que necesitas para decidir mejor: filtros inteligentes, mapas con
                distancia a la U, reseñas reales y favoritos.</p>

            <div class="about__main">
                <article class="about__icons">
                    <img src= "{{ asset('assets/img/images/shapes.svg') }}" class="about__icon" alt="">
                    <h3 class="about__title">Filtros inteligentes</h3>
                    <p class="about__paragrah">Busca por precio, servicios incluidos (internet, amoblado, aire) y tipo
                        de alojamiento (residencia, apartamento, pensión).</p>
                </article>

                <article class="about__icons">
                    <img src="{{ asset('assets/img/images/paint.svg') }}" class="about__icon" alt="">
                    <h3 class="about__title">Mapas y distancia</h3>
                    <p class="about__paragrah">Visualiza la ubicación y la distancia a la Universidad; descubre rutas de
                        transporte cuando el alojamiento esté más lejos.</p>
                </article>

                <article class="about__icons">
                    <img src="{{ asset('assets/img/images/code.svg') }}" class="about__icon" alt="">
                    <h3 class="about__title">Reseñas y favoritos</h3>
                    <p class="about__paragrah">Lee calificaciones de otros estudiantes y guarda en favoritos tus
                        opciones preferidas para compararlas después.</p>
                </article>
            </div>
        </section>

        <section class="knowledge">
            <div class="knowledge__container container">
                <div class="knowledege__texts">
                    <h2 class="subtitle">Encuentra y publica alojamientos en minutos</h2>
                    <p class="knowledge__paragraph">Si eres estudiante, descubre opciones confiables; si eres
                        arrendador, publica con fotos, servicios y precio. Todo centralizado para decidir mejor.</p>
                    <a href="#" class="cta">Explorar residencias</a>
                </div>

                <figure class="knowledge__picture">
                    <img src= "{{ asset('assets/img/images/macbook.png') }}" class="knowledge__img" alt="Vista de la plataforma">
                </figure>
            </div>
        </section>

        <section class="price container" id="top-pensiones">
  <h2 class="subtitle">Mejores pensiones</h2>

  <div class="price__table" role="list">
    <!-- 3.º lugar -->
    <article class="price__element" role="listitem">
      <p class="price__name">3.º lugar</p>
      <h3 class="price__price">Residencia Aurora — $350.000/mes</h3>

      <div class="price__items">
        <p class="price__features">Habitación individual · Baño compartido</p>
        <p class="price__features">Wi-Fi y cocina equipada</p>
        <p class="price__features">A 900 m del campus</p>
      </div>

      <a href="alojamientos.php?q=Residencia%20Aurora" class="price__cta">Ver detalles</a>
    </article>

    <!-- 1.º lugar (destacada al centro) -->
    <article class="price__element price__element--best" role="listitem" aria-label="Mejor opción">
      <p class="price__name">1.º lugar</p>
      <h3 class="price__price">$300.000/mes — Pensión Campus Norte</h3>

      <div class="price__items">
        <p class="price__features">Habitación con baño · Amobladada</p>
        <p class="price__features">Lavadora, parqueadero, seguridad 24 h</p>
        <p class="price__features">A 500 m del campus</p>
      </div>

      <a href="alojamientos.php?q=Campus%20Norte" class="price__cta">Ver detalles</a>
    </article>

    <!-- 2.º lugar -->
    <article class="price__element" role="listitem">
      <p class="price__name">2.º lugar</p>
      <h3 class="price__price">$320.000/mes — Casa Estudiantes Central</h3>

      <div class="price__items">
        <p class="price__features">2–4 habitaciones · Baño privado</p>
        <p class="price__features">Incluye servicios y limpieza</p>
        <p class="price__features">A 750 m del campus</p>
      </div>

      <a href="alojamientos.php?q=Central" class="price__cta">Ver detalles</a>
    </article>
  </div>
</section>


        <section class="testimony">
            <div class="testimony__container container">
                <img src="{{ asset('assets/img/images/leftarrow.svg') }}"  class="testimony__arrow" id="before" alt="Anterior">

                <section class="testimony__body testimony__body--show" data-id="1">
                    <div class="testimony__texts">
                        <h2 class="subtitle">Soy Valeria, <span class="testimony__course">estudiante de Ingeniería.</span></h2>
                        <p class="testimony__review">Encontré una residencia a 10 minutos de la U con internet y
                            amoblado. Las reseñas me ayudaron a decidir segura.</p>
                    </div>

                    <figure class="testimony__picture">
                        <img src= "{{ asset('assets/img/images/borra1.png') }}" class="testimony__img" alt="Valeria">
                    </figure>
                </section>

                <section class="testimony__body" data-id="2">
                    <div class="testimony__texts">
                        <h2 class="subtitle">Soy Andrés, <span class="testimony__course">estudiante de Medicina.</span></h2>
                        <p class="testimony__review">Usé los filtros por precio y distancia para ajustar mi presupuesto
                            y ahorrar tiempo en traslados.</p>
                    </div>

                    <figure class="testimony__picture">
                        <img src="{{ asset('assets/img/images/borar22.jpeg') }}" class="testimony__img" alt="Andrés">
                    </figure>
                </section>

                <section class="testimony__body" data-id="3">
                    <div class="testimony__texts">
                        <h2 class="subtitle">Soy Karen, <span class="testimony__course">estudiante de Derecho.</span></h2>
                        <p class="testimony__review">Me encantó poder guardar favoritos y comparar. Cerré contrato en
                            dos días.</p>
                    </div>

                    <figure class="testimony__picture">
                        <img src="{{ asset('assets/img/images/borar3.jpeg') }}" class="testimony__img" alt="Karen">
                    </figure>
                </section>

                <section class="testimony__body" data-id="4">
                    <div class="testimony__texts">
                        <h2 class="subtitle">Soy Kevin, <span class="testimony__course">estudiante de Sistemas.</span></h2>
                        <p class="testimony__review">La ubicación en el mapa y las rutas de transporte me ayudaron a
                            calcular tiempos reales.</p>
                    </div>

                    <figure class="testimony__picture">
                        <img src="{{ asset('assets/img/images/face4.jpg') }}"class="testimony__img" alt="Kevin">
                    </figure>
                </section>

                <img src="{{ asset('assets/img/images/rightarrow.svg') }}"class="testimony__arrow" id="next" alt="Siguiente">
            </div>
        </section>

        <section class="questions container" id="como-funciona">
            <h2 class="subtitle">Preguntas frecuentes</h2>
            <p class="questions__paragraph">Resolvemos las dudas más comunes de estudiantes y arrendadores.</p>

            <section class="questions__container">
                <article class="questions__padding">
                    <div class="questions__answer">
                        <h3 class="questions__title">¿Cómo busco alojamientos?
                            <span class="questions__arrow">
                                <img src="{{ asset('assets/img/images/arrow.svg') }}" class="questions__img" alt="">
                            </span>
                        </h3>

                        <p class="questions__show">Usa los filtros por precio, servicios y distancia. Abre cada ficha
                            para ver fotos, ubicación y reseñas de estudiantes.</p>
                    </div>
                </article>

                <article class="questions__padding">
                    <div class="questions__answer">
                        <h3 class="questions__title">¿Cómo publico como arrendador?
                            <span class="questions__arrow">
                                <img src="{{ asset('assets/img/images/arrow.svg') }}"class="questions__img" alt="">
                            </span>
                        </h3>

                        <p class="questions__show">Crea tu cuenta, completa los datos de tu residencia (dirección,
                            precio, servicios y fotos) y envía para revisión.</p>
                    </div>
                </article>

                <article class="questions__padding">
                    <div class="questions__answer">
                        <h3 class="questions__title">¿Las reseñas son verificadas?
                            <span class="questions__arrow">
                                <img src= "{{ asset('assets/img/images/arrow.svg') }}"  class="questions__img" alt="">
                            </span>
                        </h3>

                        <p class="questions__show">Las reseñas las realizan usuarios registrados y se moderan para
                            mantener información útil y confiable.</p>
                    </div>
                </article>
            </section>

            <section class="questions__offer">
                <h2 class="subtitle">¿Listo para encontrar tu nuevo hogar?</h2>
                <p class="questions__copy">Explora las opciones disponibles, compara con tranquilidad y elige la
                    residencia que mejor se adapte a tu vida universitaria.</p>
                <a href="#" class="cta">Empezar ahora</a>
            </section>
        </section>
    </main>

    <footer class="footer">
        <section class="footer__container container">
            <nav class="nav nav--footer">
                <h2 class="footer__title">HABBI</h2>

                <ul class="nav__link nav__link--footer">
                    <li class="nav__items">
                        <a href="#" class="nav__links">Inicio</a>
                    </li>
                    <li class="nav__items">
                        <a href="#" class="nav__links">Cómo funciona</a>
                    </li>
                    <li class="nav__items">
                        <a href="#" class="nav__links">Publicar</a>
                    </li>
                    <li class="nav__items">
                        <a href="#" class="nav__links">Contactro</a>
                    </li>
                     <li class="nav__items">
                        <a href="#" class="nav__links">Registrarte</a>
                    </li>
                </ul>
            </nav>

            <form class="footer__form" action="https://formspree.io/f/mknkkrkj" method="POST">
                <h2 class="footer__newsletter">Suscríbete a las novedades</h2>
                <div class="footer__inputs">
                    <input type="email" placeholder="Email:" class="footer__input" name="_replyto">
                    <input type="submit" value="Registrarme" class="footer__submit">
                </div>
            </form>
        </section>

        <section class="footer__copy container">
            <div class="footer__social">
                <a href="#" class="footer__icons"><img src="{{ asset('assets/img/images/facebook.svg') }}"class="footer__img" alt="Facebook"></a>
                <a href="#" class="footer__icons"><img src="{{ asset('assets/img/images/twitter.svg') }}" class="footer__img" alt="Twitter"></a>
                <a href="#" class="footer__icons"><img src="{{ asset('assets/img/images/youtube.svg') }}" class="footer__img" alt="YouTube"></a>
            </div>

            <h3 class="footer__copyright">© HABBI — Todos los derechos reservados</h3>
        </section>
    </footer>

    <script src= "{{asset('dist/js/slider.js') }}" ></script>
    <script src="{{asset('dist/js/questions.js') }}"></script>
    <script src="{{asset('dist/js/menu.js') }}"></script>
</body>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('userMenuBtn');
  const dd  = document.getElementById('userDropdown');

  if (!btn || !dd) return;

  const toggle = () => {
    dd.style.display = (dd.style.display === 'block') ? 'none' : 'block';
  };

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    toggle();
  });

  // cerrar al hacer clic fuera
  document.addEventListener('click', (e) => {
    if (dd.style.display === 'block' && !dd.contains(e.target) && !btn.contains(e.target)) {
      dd.style.display = 'none';
    }
  });

  // si se abre/cierra el menú móvil, cerramos el dropdown
  const navMenuToggle = document.querySelector('.nav__menu, .nav__close');
  if (navMenuToggle) {
    navMenuToggle.addEventListener('click', () => dd.style.display = 'none');
  }
});
</script>

</html>
