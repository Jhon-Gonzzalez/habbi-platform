<?php

namespace App\Support;

/** Límites reales de subida que impone la configuración de PHP. */
class Subidas
{
    /**
     * Tamaño máximo del formulario completo, en bytes.
     * Es el menor entre post_max_size y el total que permiten los archivos.
     */
    public static function maxPost(): int
    {
        $post = self::aBytes(ini_get('post_max_size'));

        // post_max_size = 0 significa «sin límite»
        return $post > 0 ? $post : PHP_INT_MAX;
    }

    /** Tamaño máximo de un archivo individual, en bytes. */
    public static function maxArchivo(): int
    {
        $archivo = self::aBytes(ini_get('upload_max_filesize'));

        return min($archivo > 0 ? $archivo : PHP_INT_MAX, self::maxPost());
    }

    /** Convierte «8M», «512K» o «1G» a bytes. */
    public static function aBytes(string|false $valor): int
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return 0;
        }

        $numero = (int) $valor;

        return match (strtolower(substr($valor, -1))) {
            'g'     => $numero * 1024 ** 3,
            'm'     => $numero * 1024 ** 2,
            'k'     => $numero * 1024,
            default => $numero,
        };
    }

    /** Formatea un número de bytes para mostrarlo («21,9 MB»). */
    public static function formatear(int $bytes): string
    {
        if ($bytes >= 1024 ** 3) {
            return number_format($bytes / 1024 ** 3, 1, ',', '.') . ' GB';
        }

        if ($bytes >= 1024 ** 2) {
            return number_format($bytes / 1024 ** 2, 1, ',', '.') . ' MB';
        }

        return number_format($bytes / 1024, 0, ',', '.') . ' KB';
    }
}
