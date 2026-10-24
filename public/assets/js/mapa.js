/* ==========================================================================
   HABBI — Mapa de alojamientos (Leaflet + OpenStreetMap)

   Sin dependencias más allá de Leaflet, que se sirve desde el propio
   proyecto. Si por lo que sea no cargó, no se rompe nada: el contenedor
   se queda como está y el resto de la página sigue funcionando. Por eso
   el listado nunca depende del mapa para mostrar resultados.
   ========================================================================== */
(function () {
  'use strict';

  /* Dónde se recuerda cómo dejó el usuario el mapa la última vez. */
  var CLAVE_CERRADO = 'habbi:mapa:cerrado';
  var CLAVE_GRANDE  = 'habbi:mapa:grande';

  /* El almacenamiento del navegador falla en ventanas privadas y con las
     cookies bloqueadas. Es una comodidad, no un requisito: si no se puede
     leer ni escribir, el mapa sale con su tamaño normal y ya está. */
  function recordar(clave, valor) {
    try { localStorage.setItem(clave, valor ? '1' : '0'); } catch (e) { /* da igual */ }
  }

  function recordado(clave) {
    try { return localStorage.getItem(clave) === '1'; } catch (e) { return false; }
  }

  function hayPreferencia(clave) {
    try { return localStorage.getItem(clave) !== null; } catch (e) { return false; }
  }

  function esMovil() {
    return window.matchMedia('(max-width: 980px)').matches;
  }

  function iniciar() {
    if (typeof L === 'undefined') return;

    /* Varios mapas en la misma página: hoy solo hay uno, pero recorrer la
       lista cuesta lo mismo que leer el primero y no deja una trampa. */
    document.querySelectorAll('[data-mapa]').forEach(montar);
  }

  function montar(caja) {
    var lienzo = caja.querySelector('.hb-mapa-lienzo');
    if (!lienzo) return;

    var puntos = JSON.parse(lienzo.getAttribute('data-puntos') || '[]');
    var unis   = JSON.parse(lienzo.getAttribute('data-universidades') || '[]');
    if (!puntos.length) return;

    /* El tamaño guardado se aplica ANTES de crear el mapa: así Leaflet
       mide el contenedor ya con su alto definitivo y no hay que
       recolocarlo nada más nacer. */
    /*
     * En el teléfono el mapa nace plegado, y en escritorio abierto.
     *
     * En una pantalla de 390 px, un mapa de 200 px de alto empuja el
     * primer alojamiento fuera de la vista y se ve poco. Vale más
     * enseñar los resultados y dejar el mapa a un toque, que es lo que
     * dice la barra: «12 alojamientos en el mapa».
     *
     * Solo si el usuario no ha elegido ya: en cuanto toca «Ver el mapa»
     * o «Ocultar», manda su elección, también en móvil.
     */
    var cerrado = hayPreferencia(CLAVE_CERRADO) ? recordado(CLAVE_CERRADO) : esMovil();
    var grande  = recordado(CLAVE_GRANDE);

    if (cerrado) caja.setAttribute('data-cerrado', '');
    if (grande)  caja.setAttribute('data-grande', '');

    var mapa = L.map(lienzo, { scrollWheelZoom: false });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; colaboradores de <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(mapa);

    var limites = [];

    /* Las universidades, en otro color: son la referencia, no la oferta. */
    unis.forEach(function (u) {
      if (!u.coords) return;
      L.circleMarker([u.coords[0], u.coords[1]], {
        radius: 9, color: '#001A49', fillColor: '#5454D4', fillOpacity: 0.9, weight: 2
      })
        .addTo(mapa)
        .bindPopup('<strong>' + u.nombre + '</strong>');
      limites.push([u.coords[0], u.coords[1]]);
    });

    /* Los alojamientos. */
    puntos.forEach(function (p) {
      var ficha = '<strong>' + p.titulo + '</strong><br>' + p.precio +
        (p.url ? '<br><a href="' + p.url + '">Ver la publicación</a>' : '');

      L.circleMarker([p.lat, p.lng], {
        radius: 8, color: '#0B6FD0', fillColor: '#2091F9', fillOpacity: 0.9, weight: 2
      })
        .addTo(mapa)
        .bindPopup(ficha);

      limites.push([p.lat, p.lng]);
    });

    function encuadrar() {
      mapa.fitBounds(limites, { padding: [30, 30], maxZoom: 15 });
    }

    encuadrar();

    /* La rueda del ratón mueve la página, no el zoom, salvo que se pulse
       sobre el mapa primero: si no, bajar por la página se queda atascado. */
    mapa.on('click', function () { mapa.scrollWheelZoom.enable(); });
    mapa.on('mouseout', function () { mapa.scrollWheelZoom.disable(); });

    conectarBotones(caja, mapa, encuadrar);
  }

  function conectarBotones(caja, mapa, encuadrar) {
    var botonVer    = caja.querySelector('[data-mapa-ver]');
    var botonTamano = caja.querySelector('[data-mapa-tamano]');

    /*
     * Leaflet guarda el tamaño del contenedor cuando se crea el mapa. Al
     * cambiarlo con CSS hay que avisarle con invalidateSize o se queda
     * dibujando sobre las medidas viejas: mosaicos a medias y el centro
     * descolocado. Y volvemos a encuadrar para que los pines sigan
     * cabiendo en el alto nuevo.
     */
    function reajustar() {
      mapa.invalidateSize();
      encuadrar();
    }

    function pintarEstado() {
      var cerrado = caja.hasAttribute('data-cerrado');
      var grande  = caja.hasAttribute('data-grande');

      if (botonVer) {
        botonVer.textContent = cerrado ? 'Ver el mapa' : 'Ocultar';
        botonVer.setAttribute('aria-expanded', cerrado ? 'false' : 'true');
      }

      if (botonTamano) {
        botonTamano.textContent = grande ? 'Reducir' : 'Ampliar';
        botonTamano.setAttribute('aria-pressed', grande ? 'true' : 'false');
        /* Con el mapa oculto, cambiar su alto no significa nada. */
        botonTamano.disabled = cerrado;
      }
    }

    if (botonVer) {
      botonVer.addEventListener('click', function () {
        var cerrado = caja.toggleAttribute('data-cerrado');
        recordar(CLAVE_CERRADO, cerrado);
        pintarEstado();
        /* Al reaparecer, el contenedor pasa de alto cero al suyo. */
        if (!cerrado) reajustar();
      });
    }

    if (botonTamano) {
      botonTamano.addEventListener('click', function () {
        recordar(CLAVE_GRANDE, caja.toggleAttribute('data-grande'));
        pintarEstado();
        reajustar();
      });
    }

    pintarEstado();
  }

  document.addEventListener('DOMContentLoaded', iniciar);
})();
