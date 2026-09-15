<?php

namespace Tests\Unit;

use App\Support\Texto;
use PHPUnit\Framework\TestCase;

class TextoTest extends TestCase
{
    /** @dataProvider casos */
    public function test_pluraliza_en_espanol(string $palabra, int $cantidad, string $esperado): void
    {
        $this->assertSame($esperado, Texto::plural($palabra, $cantidad));
    }

    public static function casos(): array
    {
        return [
            'vocal átona'        => ['alojamiento', 3, 'alojamientos'],
            'consonante'         => ['huésped', 2, 'huéspedes'],
            'una unidad'         => ['huésped', 1, 'huésped'],
            'cero es plural'     => ['resultado', 0, 'resultados'],
            'aguda en -ión'      => ['publicación', 4, 'publicaciones'],
            'aguda en -és'       => ['inglés', 2, 'ingleses'],
            'terminada en -z'    => ['lápiz', 2, 'lápices'],
            'invariable en -s'   => ['crisis', 2, 'crisis'],
            'con ñ'              => ['reseña', 5, 'reseñas'],
            'consonante d'       => ['ciudad', 2, 'ciudades'],
            'frase completa'     => ['Publicación activa', 2, 'Publicaciones activas'],
            'frase en singular'  => ['Publicación activa', 1, 'Publicación activa'],
        ];
    }
}
