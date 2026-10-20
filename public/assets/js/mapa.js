/* ==========================================================================
   HABBI — Mapa de alojamientos (Leaflet + OpenStreetMap)

   Sin dependencias más allá de Leaflet. Si Leaflet no cargó (sin red, un
   bloqueador, el CDN caído), no se rompe nada: el contenedor se queda
   vacío y el resto de la página sigue funcionando. Por eso el listado
   nunca depende del mapa para mostrar resultados.
   ========================================================================== */
(function () {
  'use strict';

  function iniciar() {
    if (typeof L === 'undefined') return;

    /* Varios mapas en la misma página: hoy solo hay uno, pero recorrer la
       lista cuesta lo mismo que leer el primero y no deja una trampa. */
    document.querySelectorAll('.hb-mapa-lienzo').forEach(pintar);
  }

  function pintar(lienzo) {
    var puntos = JSON.parse(lienzo.getAttribute('data-puntos') || '[]');
    var unis   = JSON.parse(lienzo.getAttribute('data-universidades') || '[]');
    if (!puntos.length) return;

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

    mapa.fitBounds(limites, { padding: [30, 30], maxZoom: 15 });

    /* La rueda del ratón mueve la página, no el zoom, salvo que se pulse
       sobre el mapa primero: si no, bajar por la página se queda atascado. */
    mapa.on('click', function () { mapa.scrollWheelZoom.enable(); });
    mapa.on('mouseout', function () { mapa.scrollWheelZoom.disable(); });
  }

  document.addEventListener('DOMContentLoaded', iniciar);
})();
