<?php

namespace App\Support;

/**
 * Añade una versión a los archivos de public/ para romper la caché.
 *
 * El navegador guarda el CSS y el JS durante días. Sin versión, un cambio
 * de diseño llega al servidor pero NO a quien ya visitó la página: sigue
 * viendo la hoja de estilos vieja y parece que el despliegue no funcionó.
 *
 * La versión es la fecha de modificación del archivo, así que cambia sola
 * en cada despliegue y no hay que acordarse de subir ningún número.
 *
 *   <link rel="stylesheet" href="@recurso('assets/css/habbi.css')">
 */
class Recurso
{
    public static function url(string $ruta): string
    {
        $ruta    = ltrim($ruta, '/');
        $absoluta = public_path($ruta);

        // Si el archivo no está (una ruta mal escrita, o un disco raro en
        // producción), se devuelve la URL sin versión en vez de reventar:
        // más vale una hoja de estilos cacheada que una página sin estilos.
        $version = is_file($absoluta) ? filemtime($absoluta) : null;

        return asset($ruta) . ($version ? '?v=' . $version : '');
    }
}
