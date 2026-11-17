/**
 * Reduce las fotos en el navegador ANTES de subirlas.
 *
 * El servidor también las recomprime, pero eso llega tarde para quien sube:
 * los megas ya viajaron. Un arrendador con el celular en 4G desde Montería
 * mandaba hasta 40 MB por publicación (8 fotos × 5 MB) y se quedaba
 * mirando la pantalla sin saber si la página se había colgado.
 *
 * Reduciendo aquí, esos 40 MB se quedan en unos 2.
 *
 * Es una mejora progresiva: si el navegador no tiene createImageBitmap, si
 * el canvas falla o si algo sale mal con una foto concreta, se sube el
 * archivo original y ya está. El formulario nunca se queda bloqueado.
 */
(function () {
    'use strict';

    var ANCHO_MAX = 1600;
    var CALIDAD   = 0.82;

    // Por debajo de esto no vale la pena: el ahorro no compensa el trabajo.
    var MINIMO_PARA_TOCAR = 400 * 1024;

    function soportado() {
        return typeof window.createImageBitmap === 'function' &&
               typeof window.DataTransfer === 'function' &&
               !!document.createElement('canvas').getContext;
    }

    /** Devuelve el File reducido, o el original si no se pudo. */
    function reducir(archivo) {
        if (!archivo.type || archivo.type.indexOf('image/') !== 0) {
            return Promise.resolve(archivo);
        }

        if (archivo.size < MINIMO_PARA_TOCAR) {
            return Promise.resolve(archivo);
        }

        // «from-image» hace que respete la orientación EXIF. Sin esto, las
        // fotos verticales de celular salen acostadas.
        return createImageBitmap(archivo, { imageOrientation: 'from-image' })
            .then(function (bitmap) {
                var escala = Math.min(1, ANCHO_MAX / bitmap.width);
                var ancho  = Math.round(bitmap.width * escala);
                var alto   = Math.round(bitmap.height * escala);

                var lienzo = document.createElement('canvas');
                lienzo.width  = ancho;
                lienzo.height = alto;
                lienzo.getContext('2d').drawImage(bitmap, 0, 0, ancho, alto);
                bitmap.close();

                return new Promise(function (resolver) {
                    lienzo.toBlob(function (blob) {
                        if (!blob || blob.size >= archivo.size) {
                            // Si quedó más grande (pasa con capturas y PNG
                            // planos), no tiene sentido cambiarla.
                            resolver(archivo);
                            return;
                        }

                        var nombre = archivo.name.replace(/\.[^.]+$/, '') + '.jpg';
                        resolver(new File([blob], nombre, { type: 'image/jpeg' }));
                    }, 'image/jpeg', CALIDAD);
                });
            })
            .catch(function () {
                return archivo;
            });
    }

    function avisar(campo, texto) {
        var id = 'hb-comprimiendo-' + (campo.name || 'fotos').replace(/\W/g, '');
        var caja = document.getElementById(id);

        if (!caja) {
            caja = document.createElement('p');
            caja.id = id;
            caja.className = 'hb-hint';
            caja.setAttribute('role', 'status');
            campo.parentNode.insertBefore(caja, campo.nextSibling);
        }

        caja.textContent = texto;
    }

    function conectar(campo) {
        campo.addEventListener('change', function () {
            var originales = Array.prototype.slice.call(campo.files);

            if (originales.length === 0) {
                return;
            }

            // Mientras se procesan, el formulario no debe poder enviarse con
            // los archivos a medio cambiar.
            var formulario = campo.form;
            var boton = formulario ? formulario.querySelector('[type="submit"]') : null;

            if (boton) { boton.disabled = true; }
            avisar(campo, 'Preparando las fotos…');

            Promise.all(originales.map(reducir)).then(function (listas) {
                var bolsa = new DataTransfer();
                var antes = 0, despues = 0;

                listas.forEach(function (archivo, i) {
                    antes   += originales[i].size;
                    despues += archivo.size;
                    bolsa.items.add(archivo);
                });

                campo.files = bolsa.files;

                var mb = function (b) { return (b / 1048576).toFixed(1); };

                avisar(campo, antes > despues
                    ? listas.length + ' fotos listas · ' + mb(antes) + ' MB → ' + mb(despues) + ' MB'
                    : listas.length + ' fotos listas');

                if (boton) { boton.disabled = false; }
            }).catch(function () {
                // Pase lo que pase, que se pueda enviar.
                avisar(campo, '');
                if (boton) { boton.disabled = false; }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!soportado()) {
            return;
        }

        var campos = document.querySelectorAll('input[type="file"][name="photos[]"], input[type="file"][name="new_photos[]"]');

        Array.prototype.forEach.call(campos, conectar);
    });
})();
