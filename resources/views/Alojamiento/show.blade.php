<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $a->title }} — HABBI</title>
  <link rel="shortcut icon" href="{{ asset('assets/img/images/Icono.png') }}" type="image/x-icon">
  <link rel="stylesheet" href="{{ asset('assets/styles.css') }}">
  <style>
  

    /* Responsive */
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

        {{-- === Usuario === --}}
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
    </li>
  @else
    <li>
      <a href="{{ route('login') }}" class="nav__links">Iniciar sesión</a>
    </li>
  @endauth
</a>
      </nav>

      <button class="menu-toggle" aria-label="Abrir menú" id="menuToggle">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>
  <main class="detail-wrap">
    {{-- Columna 1: Galería --}}
    <section class="detail-gallery">
      <div class="thumbs" id="thumbs">
        @foreach($photoUrls as $i => $url)
          <button class="thumb {{ $i===0 ? 'active' : '' }}" data-src="{{ $url }}">
            <img src="{{ $url }}" alt="Foto {{ $i+1 }} de {{ $a->title }}">
          </button>
        @endforeach
      </div>

      <div class="main-photo" id="mainPhoto">
        @if(count($photoUrls))
          <img src="{{ $photoUrls[0] }}" alt="Portada de {{ $a->title }}">
        @else
          <img src="{{ asset('assets/img/images/no-image.png') }}" alt="Sin imagen">
        @endif
      </div>
    </section>

    {{-- Columna 2: Información --}}
    <section class="detail-info">
      <h1 class="detail-title">{{ $a->title }}</h1>
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

    {{-- Columna 3: Acciones --}}
    <aside class="detail-aside">
      <div class="action-card">
        <div class="price">
          ${{ number_format($a->price, 0, ',', '.') }} <span class="muted">/{{ $a->price_period }}</span>
        </div>
        <div class="muted" style="margin-top:6px">
          Publicado por: <strong>{{ $a->user->name ?? 'Anfitrión' }}</strong>
        </div>
          <a class="btn btn-primary" id="btnDirections">Cómo llegar (Google Maps)</a>

        @if($a->phone)
          <a class="btn btn-primary" href="https://wa.me/{{ preg_replace('/\D/','',$a->phone) }}?text={{ urlencode('Hola, vi tu alojamiento "'.$a->title.'" en HABBI y me interesa.') }}" target="_blank" rel="noopener">Contactar por WhatsApp</a>
          <a class="btn" href="tel:{{ preg_replace('/\D/','',$a->phone) }}">Llamar</a>
        @else
          <button class="btn btn-primary" disabled>Sin teléfono disponible</button>
        @endif

        <a class="btn" href="{{ route('alojamiento.index') }}">Volver a los resultados</a>
      </div>
    </aside>
  </main>

  

  <script>
    // Thumbs -> cambia la imagen principal
    const thumbs = document.querySelectorAll('#thumbs .thumb');
    const mainPhoto = document.querySelector('#mainPhoto img');
    thumbs.forEach(btn=>{
      btn.addEventListener('click', ()=>{
        thumbs.forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        const src = btn.getAttribute('data-src');
        mainPhoto.setAttribute('src', src);
      });
    });

    // Dropdown del usuario (usa tus clases existentes)
    document.querySelectorAll('.user-toggle').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        btn.parentElement.classList.toggle('open');
      });
    });
    document.addEventListener('click', (e)=>{
      document.querySelectorAll('.user-menu.open').forEach(m=>{
        if (!m.contains(e.target)) m.classList.remove('open');
      });
    });
  </script>

  <style>
    /* Mini estilos para el dropdown usando tu nav */
    .user-menu{ position:relative }
    .user-toggle{ background:transparent; border:0; color:#fff; font:inherit; cursor:pointer }
    .user-dropdown{
      position:absolute; right:0; top:calc(100% + 8px);
      background:#000; border:1px solid rgba(255,255,255,.15);
      border-radius:10px; min-width:200px; padding:8px;
      display:none; z-index:99;
    }
    .user-menu.open .user-dropdown{ display:block }
    .user-dropdown a, .logout-btn{
      display:block; width:100%; text-align:left; padding:10px 12px; color:#fff; text-decoration:none; background:transparent; border:0; cursor:pointer; border-radius:8px;
    }
    .user-dropdown a:hover, .logout-btn:hover{ background:#111 }
    .logout-btn{ font:inherit }
  </style>

  <script>
(function () {
  const btn = document.getElementById('btnDirections');
  if (!btn) return;

  const destLat = {{ $a->lat ?? 'null' }};
  const destLng = {{ $a->lng ?? 'null' }};
  const destAddress = @json(trim(($a->address ? $a->address . ', ' : '') . ($a->city ?? '')));

  function buildDest() {
    if (destLat !== null && destLng !== null) return `${destLat},${destLng}`;
    return encodeURIComponent(destAddress || 'Colombia');
  }

  function openDirections(origin) {
    const destination = buildDest();
    const url =
      `https://www.google.com/maps/dir/?api=1&destination=${destination}` +
      (origin ? `&origin=${origin}` : '') +
      `&travelmode=driving`;
    window.open(url, '_blank', 'noopener');
  }

  btn.addEventListener('click', (e) => {
    e.preventDefault();

    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const origin = encodeURIComponent(`${pos.coords.latitude},${pos.coords.longitude}`);
          openDirections(origin);
        },
        () => openDirections(null),
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
      );
    } else {
      openDirections(null);
    }
  });
})();
</script>

</body>
</html>
