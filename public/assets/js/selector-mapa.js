/* ==========================================================================
   HABBI — Selector de coordenadas del formulario de publicación

   El arrendador arrastra un pin y se guardan lat/lng en dos campos
   ocultos. No se usa ningún servicio de geocodificación: convertir
   «Calle 53 con Carrera 13» en coordenadas exige una clave de API y, en
   Montería, acierta poco. Es más honesto pedirle que señale el punto.

   Si Leaflet no cargó, el bloque queda oculto y el formulario se envía
   sin coordenadas: la publicación funciona igual, solo no sale en el
   filtro por distancia.
   ========================================================================== */
(function () {
  'use strict';

  /* Centro de Montería: donde arranca el mapa cuando aún no hay punto. */
  var MONTERIA = [8.7479, -75.8814];

  function iniciar() {
    var caja = document.getElementById('selector-mapa');
    if (!caja) return;

    if (typeof L === 'undefined') {
      caja.hidden = true;
      return;
    }

    var campoLat = document.getElementById('lat');
    var campoLng = document.getElementById('lng');
    var aviso    = document.getElementById('selector-mapa-aviso');
    var borrar   = document.getElementById('selector-mapa-borrar');
    var lienzo   = caja.querySelector('.hb-selector__lienzo');

    var hayPunto = campoLat.value !== '' && campoLng.value !== '';
    var centro   = hayPunto
      ? [parseFloat(campoLat.value), parseFloat(campoLng.value)]
      : MONTERIA;

    var mapa = L.map(lienzo, { scrollWheelZoom: false })
      .setView(centro, hayPunto ? 16 : 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; colaboradores de <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(mapa);

    var pin = null;

    function escribir(lat, lng) {
      /* Seis decimales son ~11 cm: más precisión es ruido, y menos
         empieza a mover el punto de calle. */
      campoLat.value = lat.toFixed(6);
      campoLng.value = lng.toFixed(6);
      aviso.textContent = 'Punto marcado en ' + campoLat.value + ', ' + campoLng.value +
        '. Arrastra el pin si no está donde querías.';
      borrar.hidden = false;
    }

    function poner(lat, lng) {
      if (pin) {
        pin.setLatLng([lat, lng]);
      } else {
        pin = L.marker([lat, lng], { draggable: true }).addTo(mapa);
        pin.on('dragend', function () {
          var p = pin.getLatLng();
          escribir(p.lat, p.lng);
        });
      }
      escribir(lat, lng);
    }

    if (hayPunto) poner(centro[0], centro[1]);

    mapa.on('click', function (e) {
      mapa.scrollWheelZoom.enable();
      poner(e.latlng.lat, e.latlng.lng);
    });

    mapa.on('mouseout', function () { mapa.scrollWheelZoom.disable(); });

    borrar.addEventListener('click', function () {
      if (pin) { mapa.removeLayer(pin); pin = null; }
      campoLat.value = '';
      campoLng.value = '';
      aviso.textContent = 'Sin punto marcado. Tu anuncio no saldrá en el filtro por distancia.';
      borrar.hidden = true;
    });
  }

  document.addEventListener('DOMContentLoaded', iniciar);
})();
