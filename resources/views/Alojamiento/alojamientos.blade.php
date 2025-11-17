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


  <!-- Leaflet CSS (si usas el mini-mapa) -->
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
  />
  <style>#mapMini{height:160px; width:100%;}</style>
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
         <a class="active" href="{{ route('publicar') }}" >Publicar</a>
        <a href="#ayuda">Ayuda</a>

@auth
  <div class="nav__items user-menu">
    <button class="nav__links user-btn" id="userMenuBtn" type="button">
      {{ Auth::user()->name }}
      <svg width="12" height="12" viewBox="0 0 24 24" style="margin-left:6px">
        <path fill="currentColor" d="M7 10l5 5 5-5z"/>
      </svg>
    </button>

    <ul class="user-dropdown" id="userDropdown" aria-label="Menú de usuario">
      <li><a class="user-item" href="{{ route('home') }}">Mi perfil</a></li>
      <li><a class="user-item" href="{{ route('alojamiento.mine') }}">Mis alojamientos</a></li>
      <li><a class="user-item" href="{{ route('publicar') }}">Publicar alojamiento</a></li>
      <li>
        <form action="{{ route('logout') }}" method="POST">
          @csrf
          <button type="submit" class="user-item user-logout">Cerrar sesión</button>
        </form>
      </li>
    </ul>
  </div>
@else
  <a href="{{ route('login') }}" class="nav__links">Iniciar sesión</a>
@endauth

</a>
      </nav>

      <button class="menu-toggle" aria-label="Abrir menú" id="menuToggle">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <div class="map-cta">
    <div id="mapMini" aria-label="Mapa de alojamientos"></div>
    <button class="btn btn-primary map-btn" id="openMap">Mostrar alojamientos en el mapa</button>
  </div>

  <main class="container layout">
    <!-- === FILTROS (form GET) === -->
    <aside class="filters" id="filters">
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
            <input type="number" name="price_min" min="0"  value="{{ request('price_min') }}" placeholder="0">
          </label>
          <label>
            <span class="label">Precio máx (COP)</span>
            <input type="number" name="price_max" min="0"  value="{{ request('price_max') }}" placeholder="5.000.000">
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
          <span class="label">Capacidad (huéspedes)</span>
          <input type="range" name="guests" min="1" max="8" value="{{ request('guests',1) }}" oninput="document.getElementById('guestsVal').textContent=this.value">
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

    <!-- === RESULTADOS === -->
    <section class="results">
      <div class="results__topbar">
        <div class="summary">
          <strong id="resultsCount">{{ $alojamientos->total() }}</strong> resultados
        </div>
        <div class="controls">
          {{-- (Opcional) Ordenar: puedes leer ?sort en el controlador --}}
          <form method="GET" action="{{ route('alojamiento.index') }}" id="sortForm" class="field compact" style="display: flex; align-items: center; gap: 0.5rem;">
  <span class="label">Ordenar por</span>
  <select name="sort" onchange="document.getElementById('sortForm').submit()">
    <option value="">Destacados</option>
    <option value="price_asc"  {{ request('sort')=='price_asc' ? 'selected' : '' }}>Precio más bajo</option>
    <option value="price_desc" {{ request('sort')=='price_desc' ? 'selected' : '' }}>Precio más alto</option>
    <option value="recent"     {{ request('sort')=='recent' ? 'selected' : '' }}>Más recientes</option>
    <option value="oldest"     {{ request('sort')=='oldest' ? 'selected' : '' }}>Más antiguos</option>
  </select>

  {{-- Mantener filtros activos --}}
  <input type="hidden" name="q" value="{{ request('q') }}">
  <input type="hidden" name="type" value="{{ request('type') }}">
  <input type="hidden" name="price_min" value="{{ request('price_min') }}">
  <input type="hidden" name="price_max" value="{{ request('price_max') }}">
  <input type="hidden" name="guests" value="{{ request('guests') }}">
  @if(request()->has('amenities'))
    @foreach(request('amenities') as $a)
      <input type="hidden" name="amenities[]" value="{{ $a }}">
    @endforeach
  @endif
</form>

          <button id="viewGrid" class="icon-btn active" title="Vista de tarjetas" aria-label="Vista de tarjetas">
            <svg width="20" height="20" viewBox="0 0 24 24"><path d="M3 3h8v8H3zM13 3h8v8h-8zM3 13h8v8H3zM13 13h8v8h-8z"/></svg>
          </button>
          <button id="viewList" class="icon-btn" title="Vista de lista" aria-label="Vista de lista">
            <svg width="20" height="20" viewBox="0 0 24 24"><path d="M3 5h18v2H3zM3 11h18v2H3zM3 17h18v2H3z"/></svg>
          </button>
        </div>
      </div>

      <div id="cards" class="cards grid">
        @forelse($alojamientos as $a)
          <article class="card">
            <div class="card__media">
              <img class="card__img"
                   src="{{ $a->cover_path ? Storage::url($a->cover_path) : asset('assets/img/placeholder.webp') }}"
                   alt="Portada de {{ $a->title }}">
              <span class="badge">
                {{ number_format($a->price,0,',','.') }} / {{ $a->price_period }}
              </span>
            </div>

            <div class="card__body">
              <div class="card__title">
                <h3>{{ $a->title }}</h3>
                <div class="price">
                  {{ number_format($a->price,0,',','.') }}
                  <small>/{{ $a->price_period }}</small>
                </div>
              </div>

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
                @auth
                 
                @endauth
              </div>
            </div>
          </article>
        @empty
          <p class="muted">No hay alojamientos que coincidan con tu búsqueda.</p>
        @endforelse
      </div>

      <div class="pagination">
        {{ $alojamientos->links() }}
      </div>
    </section>
  </main>
\
  <!-- Modal detalle (placeholder) -->
  <dialog id="detailModal" class="modal">
    <div class="modal__content">
      <button class="modal__close" id="modalClose" aria-label="Cerrar">&times;</button>
      <div id="modalBody"></div>
    </div>
  </dialog>

  <footer class="site-footer">
    <div class="container">
      <p>&copy; <span id="year"></span> HABBI. Todos los derechos reservados.</p>
    </div>
  </footer>

  <!-- Leaflet JS (si usas el mini-mapa) -->
  <script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
  ></script>

  <script>
    // Año dinámico
    document.getElementById('year').textContent = new Date().getFullYear();

    // Menú móvil simple
    document.getElementById('menuToggle')?.addEventListener('click', ()=>{
      const nav = document.querySelector('.main-nav');
      nav.style.display = (nav.style.display === 'flex') ? 'none' : 'flex';
    });
  </script>
  
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
