
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Publicar alojamiento — habbi-web</title>

  <!-- Favicon: si tu carpeta es /habbi-web/images/Icono.png, esta ruta está OK -->
  <link rel="shortcut icon" href="{{ asset('assets/img/image/Icono.png') }}" type="image/x-icon">

  <!-- CSS principal. Si publicar.html está en /habbi-web/, esta ruta relativa funciona -->
 <link rel="stylesheet" href="{{ asset('assets/styles.css') }}">

</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="{{ route('home') }}" aria-label="Ir a página principal">
        <svg width="28" height="28" viewBox="0 0 24 24" aria-hidden="true" class="brand-icon">
          <path d="M12 2l9 7-1.5 2L12 5 4.5 11 3 9l9-7zm0 6l8.5 6.61V22H15v-5H9v5H3.5v-7.39L12 8z"></path>
        </svg>
        <span >habbi-web</span>
      </a>
      <nav class="main-nav">
        <a href="{{ route('home') }}">Inicio</a>
        <a class="active" href="{{ route('alojamiento.index') }}">Alojamientos</a>
        <a href="#ayuda">Ayuda</a>
        <!-- Ajusta esta ruta si tu login está en otro sitio -->
        <a href="{{ route('login') }}">Iniciar sesión</a>
      </nav>
      <button class="menu-toggle" aria-label="Abrir menú" id="menuToggle">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <main class="container publish-wrap">
    <section class="form-card">
      <header class="form-head">
        <div>
          <h1>Publicar alojamiento</h1>
          <p class="muted">Completa los campos y sube fotos claras. Los datos con * son obligatorios.</p>
        </div>
        <a class="btn btn-text" href="{{ route('home') }}">Volver</a>
      </header>
       <!-- VALIDAR ERRORES -->
@if ($errors->any())
  <div class="alert alert-danger" style="margin-bottom: 1rem;">
    <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

      <!-- Ajusta la acción si tu script PHP está en otra carpeta -->
      <form class="publish-form" action="{{route('alojamientos.store') }}" method="post" enctype="multipart/form-data" novalidate>
       @csrf  
      <!-- Datos básicos -->
        <div class="form-section">
          <h2>Datos básicos</h2>
          <div class="form-grid">
            <label class="field">
              <span class="label">Título *</span>
              <input type="text" name="title" placeholder="Ej: Habitación amoblada cerca del centro" required>
            </label>

            <label class="field">
              <span class="label">Tipo *</span>
              <select name="type" required>
                <option value="" disabled selected>Selecciona…</option>
                <option>Habitación</option>
                <option>Estudio</option>
                <option>Apartamento</option>
                <option>Loft</option>
                <option>Suite</option>
              </select>
            </label>

            <label class="field">
              <span class="label">Precio *</span>
              <input type="number" name="price" min="0" step="1000" placeholder="Ej: 800000" required>
            </label>

            <label class="field">
              <span class="label">Periodo *</span>
              <select name="price_period" required>
                <option value="mes">por mes</option>
                <option value="noche">por noche</option>
              </select>
            </label>

            <label class="field">
              <span class="label">Capacidad (huéspedes) *</span>
              <input type="number" name="guests" min="1" max="8" value="1" required>
            </label>
          </div>
        </div>

        <!-- Ubicación -->
        <div class="form-section">
          <h2>Ubicación</h2>
          <div class="form-grid">
            <label class="field">
              <span class="label">Ciudad *</span>
              <input type="text" name="city" placeholder="Ej: Montería" required>
            </label>

            <label class="field">
              <span class="label">Barrio / Zona</span>
              <input type="text" name="neighborhood" placeholder="Ej: El Recreo">
            </label>

            <label class="field" style="grid-column: 1 / -1;">
              <span class="label">Dirección</span>
              <input type="text" name="address" placeholder="Calle 10 # 15 - 20">
            </label>
          </div>
        </div>

        <!-- Comodidades -->
        <div class="form-section">
          <h2>Comodidades</h2>
          <div class="chipset">
            <label class="chip"><input type="checkbox" name="amenities[]" value="Wifi"> Wifi</label>
            <label class="chip"><input type="checkbox" name="amenities[]" value="Lavadora"> Lavadora</label>
            <label class="chip"><input type="checkbox" name="amenities[]" value="Parqueadero"> Parqueadero</label>
            <label class="chip"><input type="checkbox" name="amenities[]" value="Cocina"> Cocina</label>
            <label class="chip"><input type="checkbox" name="amenities[]" value="Pet Friendly"> Pet Friendly</label>
            <label class="chip"><input type="checkbox" name="amenities[]" value="Amoblado"> Amoblado</label>
            <label class="chip"><input type="checkbox" name="amenities[]" value="Aire acondicionado"> Aire acondicionado</label>
            <label class="chip"><input type="checkbox" name="amenities[]" value="Baño privado"> Baño privado</label>
          </div>
        </div>

        <!-- Descripción -->
        <div class="form-section">
          <h2>Descripción</h2>
          <label class="field">
            <span class="label">Cuéntanos más *</span>
            <textarea name="description" rows="5" placeholder="Incluye reglas, condiciones, qué está incluido…" required></textarea>
          </label>
        </div>

        <!-- Fotos -->
        <div class="form-section">
          <h2>Fotos</h2>
          <div class="uploader">
            <input id="photos" name="photos[]" type="file" accept=".jpg,.jpeg,.png,.webp" multiple>
            <p class="muted">JPG/PNG/WEBP — hasta 8 fotos, máx. 5MB c/u. La primera será la portada.</p>
          </div>
        </div>

        <!-- Contacto -->
        <div class="form-section">
          <h2>Contacto</h2>
          <div class="form-grid">
            <label class="field">
              <span class="label">Teléfono / WhatsApp</span>
              <input type="text" name="phone" placeholder="+57 300 000 0000">
            </label>
          </div>
        </div>

        <footer class="form-actions">
          <button type="submit" class="btn btn-primary">Publicar</button>
          <a class="btn" href="{{ route('home') }}">Cancelar</a>
        </footer>
      </form>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>&copy; <span id="year"></span> habbi-web. Todos los derechos reservados.</p>
    </div>
  </footer>

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
</html>
