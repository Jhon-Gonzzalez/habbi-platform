<?php

namespace App\Services;

/**
 * Reduce y recomprime una imagen antes de guardarla.
 *
 * Un celular de hoy saca fotos de 4000px y 5 MB. Guardarlas tal cual tiene
 * dos costes, y el segundo es el que duele:
 *
 *   - El disco del hosting compartido y su límite de archivos.
 *   - Cada visitante que abra la publicación se las descarga enteras. Desde
 *     Montería, con datos móviles, una galería de 8 fotos sin tocar son
 *     40 MB y una espera larguísima.
 *
 * A 1600px de ancho una foto se ve perfecta en cualquier pantalla y pesa
 * unos 250 KB. La galería entera baja de 40 MB a 2.
 */
class ImagenOptimizada
{
    /** Más ancho que esto no se nota en pantalla y sí en la factura de datos. */
    public const ANCHO_MAX = 1600;

    /** 82 es donde la pérdida deja de verse a simple vista. */
    public const CALIDAD = 82;

    /**
     * Devuelve el JPEG optimizado, o null si no era una imagen que GD entienda.
     *
     * Se trabaja sobre el contenido y no sobre la extensión: un .jpg puede
     * ser cualquier cosa, y a GD eso le sienta mal.
     */
    public function comprimir(string $contenido, int $anchoMax = self::ANCHO_MAX): ?string
    {
        $imagen = @imagecreatefromstring($contenido);

        if ($imagen === false) {
            return null;
        }

        $imagen = $this->reducir($imagen, $anchoMax);

        // Las fotos de celular suelen traer una orientación EXIF que el
        // navegador respeta pero GD no, así que al recomprimir salen
        // giradas. Se corrige antes de escribir.
        $imagen = $this->enderezar($imagen, $contenido);

        ob_start();
        imagejpeg($imagen, null, self::CALIDAD);
        imagedestroy($imagen);

        return (string) ob_get_clean();
    }

    /** @return \GdImage */
    private function reducir(\GdImage $imagen, int $anchoMax): \GdImage
    {
        if (imagesx($imagen) <= $anchoMax) {
            return $imagen;
        }

        $reducida = imagescale($imagen, $anchoMax);

        if ($reducida === false) {
            return $imagen;
        }

        imagedestroy($imagen);

        return $reducida;
    }

    /** Aplica la rotación que indique el EXIF, si es que hay. */
    private function enderezar(\GdImage $imagen, string $contenido): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $imagen;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($contenido));

        $grados = match ($exif['Orientation'] ?? 1) {
            3       => 180,
            6       => -90,
            8       => 90,
            default => 0,
        };

        if ($grados === 0) {
            return $imagen;
        }

        $girada = imagerotate($imagen, $grados, 0);

        if ($girada === false) {
            return $imagen;
        }

        imagedestroy($imagen);

        return $girada;
    }
}
