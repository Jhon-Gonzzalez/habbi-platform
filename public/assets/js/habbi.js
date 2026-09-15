/* ==========================================================================
   HABBI — Comportamiento de la interfaz
   Sin dependencias. Cada bloque comprueba si su elemento existe.
   ========================================================================== */
(function () {
  'use strict';

  /* ---------- Navbar móvil ---------- */
  function initNavbar() {
    var toggle = document.getElementById('navToggle');
    var links  = document.getElementById('navLinks');
    if (!toggle || !links) return;

    toggle.addEventListener('click', function () {
      var abierto = links.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
  }

  /* ---------- Menú desplegable de usuario ---------- */
  function initUserMenu() {
    var btn  = document.getElementById('userMenuBtn');
    var menu = document.getElementById('userMenu');
    if (!btn || !menu) return;

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var abierto = menu.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });

    document.addEventListener('click', function (e) {
      if (!menu.contains(e.target) && !btn.contains(e.target)) {
        menu.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') menu.classList.remove('is-open');
    });
  }

  /* ---------- Cerrar alertas ---------- */
  function initAlerts() {
    document.querySelectorAll('[data-cerrar-alerta]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var alerta = btn.closest('.hb-alert');
        if (alerta) alerta.remove();
      });
    });
  }

  /* ---------- Selector de estrellas ---------- */
  function initRating() {
    var widget = document.getElementById('ratingWidget');
    if (!widget) return;

    var input    = document.getElementById('ratingValue');
    var etiqueta = document.getElementById('ratingLabel');
    var botones  = Array.prototype.slice.call(widget.querySelectorAll('button'));
    var textos   = ['', 'Muy malo', 'Malo', 'Aceptable', 'Bueno', 'Excelente'];

    function pintar(valor) {
      botones.forEach(function (b, i) { b.classList.toggle('is-on', i < valor); });
      if (etiqueta) etiqueta.textContent = textos[valor] || 'Selecciona una calificación';
    }

    botones.forEach(function (btn, i) {
      var valor = i + 1;
      btn.addEventListener('mouseenter', function () { pintar(valor); });
      btn.addEventListener('click', function () {
        input.value = valor;
        pintar(valor);
      });
    });

    widget.addEventListener('mouseleave', function () { pintar(parseInt(input.value, 10) || 0); });

    pintar(parseInt(input.value, 10) || 0);
  }

  /* ---------- Galería del detalle ---------- */
  function initGallery() {
    var principal = document.getElementById('galeriaPrincipal');
    if (!principal) return;

    document.querySelectorAll('[data-galeria-thumb]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        principal.src = btn.dataset.galeriaThumb;
        document.querySelectorAll('[data-galeria-thumb]').forEach(function (b) {
          b.classList.toggle('is-active', b === btn);
        });
      });
    });
  }

  /* ---------- Subida de fotos con previsualización ---------- */
  function initUploader() {
    document.querySelectorAll('[data-uploader]').forEach(function (zona) {
      var input   = zona.querySelector('input[type="file"]');
      var destino = document.querySelector(zona.dataset.uploader);
      if (!input || !destino) return;

      ['dragenter', 'dragover'].forEach(function (ev) {
        zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.add('is-over'); });
      });
      ['dragleave', 'drop'].forEach(function (ev) {
        zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.remove('is-over'); });
      });
      zona.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files.length) {
          input.files = e.dataTransfer.files;
          input.dispatchEvent(new Event('change'));
        }
      });
      zona.addEventListener('click', function () { input.click(); });

      input.addEventListener('change', function () {
        destino.innerHTML = '';

        Array.prototype.slice.call(input.files).forEach(function (file, i) {
          if (!file.type.startsWith('image/')) return;

          var fig = document.createElement('div');
          fig.className = 'hb-thumb' + (i === 0 ? ' is-cover' : '');

          var img = document.createElement('img');
          img.src = URL.createObjectURL(file);
          img.alt = file.name;
          img.onload = function () { URL.revokeObjectURL(img.src); };
          fig.appendChild(img);

          if (i === 0) {
            var tag = document.createElement('span');
            tag.className = 'hb-thumb__tag';
            tag.textContent = 'Portada';
            fig.appendChild(tag);
          }

          destino.appendChild(fig);
        });
      });
    });
  }

  /* ---------- Gestión de fotos existentes (edición) ---------- */
  function initPhotoManager() {
    var gestor = document.getElementById('gestorFotos');
    if (!gestor) return;

    gestor.addEventListener('click', function (e) {
      var btn = e.target.closest('button[data-accion]');
      if (!btn) return;

      var thumb = btn.closest('.hb-thumb');
      var ruta  = thumb.dataset.ruta;

      if (btn.dataset.accion === 'portada') {
        document.getElementById('coverPath').value = ruta;
        gestor.querySelectorAll('.hb-thumb').forEach(function (t) { t.classList.remove('is-cover'); });
        gestor.querySelectorAll('.hb-thumb__tag').forEach(function (t) { t.remove(); });
        thumb.classList.add('is-cover');
        var tag = document.createElement('span');
        tag.className = 'hb-thumb__tag';
        tag.textContent = 'Portada';
        thumb.appendChild(tag);
      }

      if (btn.dataset.accion === 'quitar') {
        var marcado = thumb.classList.toggle('is-removed');
        var oculto  = thumb.querySelector('input[type="hidden"]');
        oculto.disabled = !marcado;
        btn.textContent = marcado ? 'Recuperar' : 'Quitar';
      }
    });
  }

  /* ---------- Confirmación antes de borrar ---------- */
  function initConfirm() {
    document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (!window.confirm(form.dataset.confirmar)) e.preventDefault();
      });
    });
  }

  /* ---------- Envío automático de filtros al cambiar el orden ---------- */
  function initAutoSubmit() {
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
      el.addEventListener('change', function () { el.form.submit(); });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initNavbar();
    initUserMenu();
    initAlerts();
    initRating();
    initGallery();
    initUploader();
    initPhotoManager();
    initConfirm();
    initAutoSubmit();
  });
})();
