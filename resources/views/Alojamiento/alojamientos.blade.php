@php
    use Illuminate\Support\Facades\Storage;
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>HABBI | Alojamientos</title>

  <link rel="shortcut icon" href="{{ asset('assets/img/image/Icono.png') }}" type="image/x-icon">
  <meta name="description" content="Explora y reserva alojamientos verificados cerca de tu universidad. Filtros rápidos, búsqueda y diseño profesional." />

  <link rel="stylesheet" href="{{ asset('assets/styles.css') }}">

  <!-- Leaflet CSS -->
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    crossorigin=""
  />
  <style>#mapMini{height:160px; width:100%;}</style>
</head>

<body>
<header class="habbi-header">
  <nav class="habbi-nav container">
      
      <a class="brand" href="{{ route('home') }}">
          <img src="{{ asset('assets/img/images/Logo.png') }}" alt="Habbi-web" class="brand-logo-nav">
      </a>

      <ul class="nav-links" id="navLinks">
         
          <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Inicio</a></li>

          <li><a href="{{ route('alojamiento.index') }}" class="{{ request()->routeIs('alojamiento.index') ? 'active' : '' }}">Buscar</a></li>

          <li><a href="{{ route('publicar') }}" class="{{ request()->routeIs('publicar') ? 'active' : '' }}">Publicar</a></li>

          <li><a href="#ayuda">Ayuda</a></li>

          @auth
              <li class="user-menu">
                  <button id="userMenuBtn" class="user-btn-nav">
                      {{ Auth::user()->name }}
                      <svg width="12" height="12"><path d="M7 10l5 5 5-5z"/></svg>
                  </button>

                  <ul class="user-dropdown" id="userDropdown">
                      <li><a href="{{ route('home') }}">Mi perfil</a></li>
                      <li><a href="{{ route('alojamiento.mine') }}">Mis alojamientos</a></li>
                      <li><a href="{{ route('publicar') }}">Publicar alojamiento</a></li>
                      <li>
                          <form action="{{ route('logout') }}" method="POST">
                              @csrf
                              <button type="submit" class="user-item">Cerrar sesión</button>
                          </form>
                      </li>
                  </ul>
              </li>
          @else
              <li><a href="{{ route('login') }}">Iniciar sesión</a></li>
          @endauth

      </ul>

      <div class="nav-toggle" id="navToggle">
          <img src="{{ asset('assets/img/images/menu.svg') }}" class="nav__img" alt="Abrir menú">
      </div>

  </nav>
</header>



<main class="container layout">

  <!-- === ASIDE FILTROS === -->
  <aside class="filters">

    <div class="mini-map-wrapper">
      <div id="mapMini"></div>
    </div>

    <div class="filters__header">
      <h2>Filtros</h2>
      <a href="{{ route('alojamiento.index') }}" class="btn btn-text">Limpiar</a>
    </div>

    <form method="GET" action="{{ route('alojamiento.index') }}">

      <label class="field">
        <span class="label">Buscar</span>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Ciudad, barrio, alojamiento…"/>
      </label>

      <div class="field two-cols">
        <label>
          <span class="label">Precio mín (COP)</span>
          <input type="number" name="price_min" min="0" value="{{ request('price_min') }}">
        </label>
        <label>
          <span class="label">Precio máx (COP)</span>
          <input type="number" name="price_max" min="0" value="{{ request('price_max') }}">
        </label>
      </div>

      <label class="field">
        <span class="label">Tipo</span>
        <select name="type">
          <option value="">Cualquiera</option>
          @foreach(['Habitación','Estudio','Apartamento','Loft','Suite'] as $t)
            <option value="{{ $t }}" @selected(request('type')===$t)>{{ $t }}</option>
          @endforeach
        </select>
      </label>

      <label class="field">
        <span class="label">Huéspedes</span>
        <input type="range" name="guests" min="1" max="8" value="{{ request('guests',1) }}"
          oninput="document.getElementById('guestsVal').textContent=this.value">
        <div class="range-hint"><span id="guestsVal">{{ request('guests',1) }}</span> huésped(es)</div>
      </label>

      <fieldset class="field">
        <legend class="label">Comodidades</legend>
        @php
          $amenReq = (array) request('amenities', []);
          $amenList = ['Wifi','Lavadora','Parqueadero','Cocina','Pet Friendly','Amoblado','Aire acondicionado','Baño privado'];
        @endphp

        @foreach($amenList as $am)
          <label class="check">
            <input type="checkbox" name="amenities[]" value="{{ $am }}" {{ in_array($am,$amenReq) ? 'checked' : '' }}>
            <span>{{ $am }}</span>
          </label>
        @endforeach
      </fieldset>

      <button class="btn btn-primary" type="submit">Aplicar filtros</button>
    </form>
  </aside>

  <!-- === LISTADO === -->
  <section class="results">

    <div class="results__topbar">
      <div class="summary">
        <strong>{{ $alojamientos->total() }}</strong> resultados
      </div>
    </div>

    <div id="cards" class="cards grid">

      @forelse($alojamientos as $a)
      <article class="card">

        <div class="card__media">
          <img class="card__img"
            src="{{ $a->cover_path ? Storage::url($a->cover_path) : asset('assets/img/placeholder.webp') }}"
            alt="Portada de {{ $a->title }}">
          <span class="badge">{{ number_format($a->price,0,',','.') }} / {{ $a->price_period }}</span>
        </div>

        <div class="card__body">

          <div class="card__title">
            <h3>{{ $a->title }}</h3>
            <div class="price">
              {{ number_format($a->price,0,',','.') }}
              <small>/{{ $a->price_period }}</small>
            </div>
          </div>

          <!-- ⭐⭐ ESTRELLAS – AQUÍ VAN (BIEN UBICADAS) ⭐⭐ -->
          @if(method_exists($a,'averageRating'))
          <div class="rating-stars" style="margin:6px 0;">
            {!! renderStars($a->averageRating()) !!}
            <span class="rating-count">({{ $a->ratingCount() }})</span>
          </div>
          @endif
          <!-- ⭐ FIN ESTRELLAS ⭐ -->

          <div class="meta">
            <span>{{ $a->type }}</span> ·
            <span>{{ $a->guests }} huésped(es)</span> ·
            <span>{{ $a->city }}@if($a->neighborhood), {{ $a->neighborhood }}@endif</span>
          </div>

          @if(is_array($a->amenities) && count($a->amenities))
          <div class="amenities">
            @foreach($a->amenities as $am)
              <span class="amenity">{{ $am }}</span>
            @endforeach
          </div>
          @endif

          <div class="actions">
            <a class="btn" href="{{ route('alojamientos.show', $a->id) }}">Ver detalles</a>
          </div>

        </div>

      </article>
      @empty
        <p class="muted">No hay alojamientos que coincidan.</p>
      @endforelse

    </div>

    <div class="pagination">
      {{ $alojamientos->links() }}
    </div>
  </section>
</main>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const miniMapDiv = document.getElementById('mapMini');
    if (!miniMapDiv) return;

    const miniMap = L.map('mapMini', {
        zoomControl: false,
        attributionControl: false
    }).setView([8.7500, -75.8800], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18
    }).addTo(miniMap);

    L.marker([8.7500, -75.8800]).addTo(miniMap);

    setTimeout(() => miniMap.invalidateSize(), 300);
});
</script>
<script>
/* Dropdown */
const btn = document.getElementById('userMenuBtn');
const dd  = document.getElementById('userDropdown');

if (btn && dd) {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', (e) => {
        if (!dd.contains(e.target) && !btn.contains(e.target)) {
            dd.style.display = 'none';
        }
    });
}

/* Mobile menu */
const mobileBtn = document.getElementById('mobileMenuBtn');
const menu = document.querySelector('.nav__link');

mobileBtn?.addEventListener('click', () => {
    menu.classList.toggle('show');
});
</script>


</body>
</html>
