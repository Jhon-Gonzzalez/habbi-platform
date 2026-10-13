<?php

namespace App\Services;

use App\Models\Alojamiento;

/**
 * Genera la imagen de marcador de un alojamiento sin foto.
 *
 * POR QUÉ ASÍ
 * -----------
 * Antes, todos los anuncios sin foto mostraban la MISMA imagen de portada
 * del sitio. Veinte tarjetas idénticas parecen un error, y además esa foto
 * sugiere un alojamiento que nadie ha visto.
 *
 * Esto dibuja una imagen distinta por anuncio, con los colores de HABBI y
 * un icono según el tipo. No intenta parecer una foto: se ve claramente
 * que falta la foto, que es la verdad.
 *
 * Es geometría pura, sin texto, a propósito: dibujar texto necesita una
 * tipografía en el servidor, y no hay ninguna con la que se pueda contar
 * en todas las máquinas donde esto va a correr.
 */
class ImagenMarcador
{
    public const ANCHO = 1200;

    public const ALTO = 900;

    /** Parejas de color para el degradado, en la familia de la marca. */
    private const PALETAS = [
        ['#2091F9', '#0B6FD0'],   // azul HABBI
        ['#5454D4', '#2E2E9E'],   // violeta
        ['#0B6FD0', '#001A49'],   // azul profundo
        ['#12996B', '#0A6B4A'],   // verde
        ['#1D293F', '#001A49'],   // navy
        ['#2091F9', '#5454D4'],   // azul a violeta
    ];

    /** @return string Los bytes del PNG. */
    public function generar(Alojamiento $alojamiento): string
    {
        // El id decide la paleta: el mismo anuncio sale siempre igual, y dos
        // anuncios seguidos salen distintos.
        [$desde, $hasta] = self::PALETAS[$alojamiento->id % count(self::PALETAS)];

        $lienzo = imagecreatetruecolor(self::ANCHO, self::ALTO);
        imageantialias($lienzo, true);

        $this->degradado($lienzo, $desde, $hasta);
        $this->destellos($lienzo, $alojamiento->id);
        $this->icono($lienzo, $alojamiento->type);

        ob_start();
        imagepng($lienzo, null, 6);
        $bytes = ob_get_clean();

        imagedestroy($lienzo);

        return $bytes;
    }

    /** Degradado en diagonal, de la esquina superior izquierda a la inferior derecha. */
    private function degradado($lienzo, string $desde, string $hasta): void
    {
        [$r1, $g1, $b1] = $this->rgb($desde);
        [$r2, $g2, $b2] = $this->rgb($hasta);

        $pasos = self::ANCHO + self::ALTO;

        for ($i = 0; $i < $pasos; $i++) {
            $t     = $i / $pasos;
            $color = imagecolorallocate(
                $lienzo,
                (int) round($r1 + ($r2 - $r1) * $t),
                (int) round($g1 + ($g2 - $g1) * $t),
                (int) round($b1 + ($b2 - $b1) * $t),
            );

            imageline($lienzo, $i, 0, 0, $i, $color);
        }
    }

    /**
     * Tres círculos grandes y muy suaves, para que el fondo no sea un plano
     * liso. La posición depende del id, así que cada anuncio sale distinto.
     */
    private function destellos($lienzo, int $semilla): void
    {
        $claro  = imagecolorallocatealpha($lienzo, 255, 255, 255, 116);
        $oscuro = imagecolorallocatealpha($lienzo, 0, 0, 40, 118);

        $posiciones = [
            [0.18 + ($semilla % 3) * 0.08, 0.22, 520, $claro],
            [0.86, 0.78 - ($semilla % 4) * 0.06, 440, $oscuro],
            [0.70 - ($semilla % 2) * 0.1, 0.12, 300, $claro],
        ];

        foreach ($posiciones as [$x, $y, $d, $color]) {
            imagefilledellipse(
                $lienzo,
                (int) (self::ANCHO * $x),
                (int) (self::ALTO * $y),
                $d, $d,
                $color,
            );
        }
    }

    /**
     * Un icono según el tipo. Una cama para lo que es una habitación dentro
     * de una casa ajena, y un edificio para lo que es una vivienda entera:
     * así se distingue de un vistazo en el listado.
     */
    private function icono($lienzo, ?string $tipo): void
    {
        $cx = (int) (self::ANCHO / 2);
        $cy = (int) (self::ALTO / 2);

        in_array($tipo, ['Habitación', 'Suite'], true)
            ? $this->cama($lienzo, $cx, $cy)
            : $this->edificio($lienzo, $cx, $cy);
    }

    /** Cama vista de lado: colchón, cabecero, almohada y patas. */
    private function cama($lienzo, int $cx, int $cy): void
    {
        $blanco = imagecolorallocatealpha($lienzo, 255, 255, 255, 58);
        $tenue  = imagecolorallocatealpha($lienzo, 255, 255, 255, 96);

        $ancho = 460;
        $izq   = $cx - (int) ($ancho / 2);
        $der   = $cx + (int) ($ancho / 2);

        // Cabecero
        imagefilledrectangle($lienzo, $izq, $cy - 150, $izq + 34, $cy + 40, $blanco);

        // Colchón
        imagefilledrectangle($lienzo, $izq, $cy - 20, $der, $cy + 40, $blanco);

        // Almohada
        imagefilledrectangle($lienzo, $izq + 54, $cy - 72, $izq + 190, $cy - 24, $tenue);

        // Patas
        imagefilledrectangle($lienzo, $izq, $cy + 40, $izq + 26, $cy + 104, $blanco);
        imagefilledrectangle($lienzo, $der - 26, $cy + 40, $der, $cy + 104, $blanco);
    }

    /** Edificio de tres plantas con ventanas. */
    private function edificio($lienzo, int $cx, int $cy): void
    {
        $blanco = imagecolorallocatealpha($lienzo, 255, 255, 255, 58);

        $ancho = 360;
        $alto  = 420;
        $izq   = $cx - (int) ($ancho / 2);
        $arr   = $cy - (int) ($alto / 2);

        imagefilledrectangle($lienzo, $izq, $arr, $izq + $ancho, $arr + $alto, $blanco);

        // Ventanas encendidas: blanco casi opaco sobre la silueta. Se probó
        // a recortarlas para dejar ver el fondo, pero el lienzo es opaco y
        // el PNG se guarda sin canal alfa, así que salían blancas igual.
        // Mejor hacerlo a propósito que fingir que se recorta.
        $ventana = imagecolorallocatealpha($lienzo, 255, 255, 255, 22);

        for ($fila = 0; $fila < 3; $fila++) {
            for ($col = 0; $col < 3; $col++) {
                $x = $izq + 46 + $col * 96;
                $y = $arr + 50 + $fila * 108;
                imagefilledrectangle($lienzo, $x, $y, $x + 62, $y + 72, $ventana);
            }
        }
    }

    /** @return array{0:int,1:int,2:int} */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
