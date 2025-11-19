<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $a->title }} — HABBI</title>
  <link rel="shortcut icon" href="{{ asset('assets/img/images/Icono.png') }}" type="image/x-icon">
  <link rel="stylesheet" href="{{ asset('assets/styles.css') }}">
  <style>

    @media (max-width: 1000px){
      .detail-wrap{ grid-template-columns: 1fr; }
      .detail-aside{ position:static }
    }
  </style>
</head>
<body>



<header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="{{ route('home') }}" aria-label="Ir a página principal">
        <svg width="28" height="28" viewBox="0 0 24 24" aria-hidden="true" class="brand-icon">
          <path d="M12 2l9 7-1.5 2L12 5 4.5 11 3 9l9-7zm0 6l8.5 6.61V22H15v-5H9v5H3.5v-7.39L12 8z"></path>
        </svg>
        <span>HABBI</span>
      </a>

      <nav class="main-nav">
        <a href="{{ route('home') }}">Inicio</a>
        <a class="active" href="{{ route('alojamiento.index') }}">Alojamientos</a>
        <a href="#ayuda">Ayuda</a>

        @auth
    <a class="nav__items user-menu">
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
    </a>
  @else
    <a href="{{ route('login') }}" class="nav__links">Iniciar sesión</a>
  @endauth
      </nav>

      <button class="menu-toggle" aria-label="Abrir menú" id="menuToggle">
        <span></span><span></span><span></span>
      </button>
    </div>
</header>

<main class="detail-wrap">

    {{-- Galería --}}
    <section class="detail-gallery">
      <div class="thumbs" id="thumbs">
        @foreach($photoUrls as $i => $url)
          <button class="thumb {{ $i===0 ? 'active' : '' }}" data-src="{{ $url }}">
            <img src="{{ $url }}" alt="Foto {{ $i+1 }} de {{ $a->title }}">
          </button>
        @endforeach
      </div>

      <div class="main-photo" id="mainPhoto">
        <img src="{{ $photoUrls[0] ?? asset('assets/img/images/no-image.png') }}" alt="Foto principal">
      </div>
    </section>

    {{-- Información --}}
    <section class="detail-info">

      <h1 class="detail-title">{{ $a->title }}</h1>

      {{-- ⭐⭐⭐⭐⭐ ESTRELLAS (PASO 7) --}}
      <div style="display:flex; align-items:center; gap:8px; margin:10px 0;">
          {!! renderStars($a->averageRating()) !!}
          <span style="font-weight:600; color:#333;">
              {{ number_format($a->averageRating(), 1) }}
          </span>
          <span style="color:#777; font-size:14px;">
              {{ $a->ratingCount() }} reseñas
          </span>
      </div>

      <div class="detail-meta">
        {{ $a->city }} @if($a->neighborhood) • {{ $a->neighborhood }} @endif
        • Capacidad: {{ $a->guests }} huésped(es)
        • Tipo: {{ $a->type }}
      </div>

      <div>
        <h3 class="subtitle" style="margin:14px 0 8px 0;">Descripción</h3>
        <p style="line-height:1.6; color:#374151">{{ $a->description }}</p>
      </div>

      @if(is_array($a->amenities) && count($a->amenities))
      {{-- =====================================================
       ⭐ FORMULARIO DE RESEÑA (PASO 8)
====================================================== --}}
<div style="margin-top:25px; padding:18px; border-radius:12px; border:1px solid #e5e7eb;">
    <h3 class="subtitle" style="margin-bottom:10px;">Dejar una reseña</h3>

    @guest
        <p style="color:#555;">Debes iniciar sesión para calificar este alojamiento.</p>
        <a href="{{ route('login') }}" class="btn btn-primary" style="margin-top:10px;">Iniciar sesión</a>
    @endguest

    @auth
        @php
            $yaCalifico = $a->ratings()->where('user_id', auth()->id())->first();
        @endphp

        @if($yaCalifico)
            <p style="color:#4b5563; font-size:15px;">
                Ya calificaste este alojamiento con 
                <strong>{{ $yaCalifico->rating }} ⭐</strong>.
            </p>
        @else
           <form action="{{ route('ratings.store', $a->id) }}" method="POST" style="margin-top:10px;">

                @csrf

                <input type="hidden" name="alojamiento_id" value="{{ $a->id }}">

                {{-- Estrellas seleccionables --}}
                <div id="ratingStars" style="display:flex; gap:6px; cursor:pointer; margin-bottom:10px;">
                    @for($i=1; $i<=5; $i++)
                        <svg data-value="{{ $i }}" width="32" height="32" fill="#ccc" viewBox="0 0 24 24" class="star-option">
                            <path d="M12 .587l3.668 7.568L24 9.748l-6 5.848L19.335 24 
                            12 19.897 4.665 24 6 15.596l-6-5.848 8.332-1.593z"/>
                        </svg>
                    @endfor
                </div>

                <input type="hidden" id="ratingValue" name="rating" value="0">

                {{-- Comentario opcional --}}
                <textarea name="comment" rows="3" placeholder="Escribe un comentario (opcional)" 
                          style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc;"></textarea>

                <button type="submit" class="btn btn-primary" style="margin-top:10px;">
                    Enviar reseña
                </button>
            </form>

            {{-- Script de estrellas interactivas --}}
            <script>
                document.addEventListener("DOMContentLoaded", () => {
                    const stars = document.querySelectorAll(".star-option");
                    const input = document.getElementById("ratingValue");

                    stars.forEach(star => {
                        star.addEventListener("mouseenter", () => {
                            const val = star.dataset.value;
                            stars.forEach(s => s.setAttribute("fill", 
                                s.dataset.value <= val ? "#FF9900" : "#ccc"
                            ));
                        });

                        star.addEventListener("click", () => {
                            const val = star.dataset.value;
                            input.value = val;
                        });

                        document.getElementById("ratingStars").addEventListener("mouseleave", () => {
                            const val = input.value;
                            stars.forEach(s => s.setAttribute("fill", 
                                s.dataset.value <= val ? "#FF9900" : "#ccc"
                            ));
                        });
                    });
                });
            </script>
        @endif
    @endauth
</div>

        <div>
          <h3 class="subtitle" style="margin:10px 0 8px 0;">Comodidades</h3>
          <div class="amenities">
            @foreach($a->amenities as $am)
              <span class="amenity">{{ $am }}</span>
            @endforeach
          </div>
        </div>
      @endif
    </section>

    {{-- Aside --}}
    <aside class="detail-aside">
      <div class="action-card">
        <div class="price">
          ${{ number_format($a->price, 0, ',', '.') }} 
          <span class="muted">/{{ $a->price_period }}</span>
        </div>

        <div class="muted" style="margin-top:6px">
          Publicado por: <strong>{{ $a->user->name ?? 'Anfitrión' }}</strong>
        </div>

        <a class="btn btn-primary" id="btnDirections">Cómo llegar (Google Maps)</a>

        @if($a->phone)
          <a class="btn btn-primary" href="https://wa.me/{{ preg_replace('/\D/','',$a->phone) }}?text={{ urlencode('Hola, vi tu alojamiento "'.$a->title.'" en HABBI y me interesa.') }}" target="_blank">Contactar por WhatsApp</a>
          <a class="btn" href="tel:{{ preg_replace('/\D/','',$a->phone) }}">Llamar</a>
        @else
          <button class="btn btn-primary" disabled>Sin teléfono disponible</button>
        @endif

        <a class="btn" href="{{ route('alojamiento.index') }}">Volver a los resultados</a>
      </div>
    </aside>

</main>

{{-- Scripts --}}
<script>
const thumbs = document.querySelectorAll('#thumbs .thumb');
const main = document.querySelector('#mainPhoto img');

thumbs.forEach(btn=>{
  btn.onclick = () => {
    thumbs.forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    main.src = btn.dataset.src;
  };
});
</script>

</body>
</html>
