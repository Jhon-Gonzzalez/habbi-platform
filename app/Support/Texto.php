<?php

namespace App\Support;

/** Utilidades de texto en español. */
class Texto
{
    /**
     * Pluraliza una palabra o frase corta en español.
     *
     * El helper Str::plural de Laravel usa reglas del inglés y produce
     * errores como «huéspeds» o «publicacións», así que aquí aplicamos
     * las reglas del español.
     *
     * @param  int|array|\Countable  $cantidad  si es 1, devuelve el singular
     */
    public static function plural(string $texto, mixed $cantidad = 2): string
    {
        $numero = is_int($cantidad) ? $cantidad : count($cantidad);

        if ($numero === 1) {
            return $texto;
        }

        // Frases: se pluraliza cada palabra («Publicación activa» → «Publicaciones activas»).
        if (str_contains($texto, ' ')) {
            return implode(' ', array_map(
                fn (string $palabra) => self::pluralizarPalabra($palabra),
                explode(' ', $texto),
            ));
        }

        return self::pluralizarPalabra($texto);
    }

    /**
     * Oculta parcialmente un correo para los listados: ana.martinez@gmail.com
     * se muestra como an•••••••@gmail.com. Reduce la exposición de datos
     * personales en pantallas que se ven de pasada o sobre el hombro.
     */
    public static function ocultarCorreo(?string $correo): string
    {
        if (!$correo || !str_contains($correo, '@')) {
            return '—';
        }

        [$usuario, $dominio] = explode('@', $correo, 2);

        $visibles = mb_substr($usuario, 0, min(2, mb_strlen($usuario)));
        $ocultos  = str_repeat('•', max(3, mb_strlen($usuario) - 2));

        return $visibles . $ocultos . '@' . $dominio;
    }

    private static function pluralizarPalabra(string $palabra): string
    {
        if ($palabra === '') {
            return $palabra;
        }

        $ultima = mb_strtolower(mb_substr($palabra, -1));

        // Agudas terminadas en vocal acentuada + n/s: pierden la tilde y añaden -es.
        // publicación → publicaciones · inglés → ingleses
        if (preg_match('/([áéíóú])([ns])$/u', mb_strtolower($palabra), $m)) {
            $sinTilde = strtr($m[1], ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);

            return mb_substr($palabra, 0, -2) . $sinTilde . $m[2] . 'es';
        }

        // Terminadas en -z: lápiz → lápices
        if ($ultima === 'z') {
            return mb_substr($palabra, 0, -1) . 'ces';
        }

        // Terminadas en vocal átona: alojamiento → alojamientos
        if (in_array($ultima, ['a', 'e', 'i', 'o', 'u'], true)) {
            return $palabra . 's';
        }

        // Terminadas en -s o -x sin acento final: el plural no cambia (crisis, tórax)
        if (in_array($ultima, ['s', 'x'], true)) {
            return $palabra;
        }

        // Terminadas en consonante: huésped → huéspedes · ciudad → ciudades
        return $palabra . 'es';
    }
}
