<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filtro por distancia y mapa de resultados (RF-4, RF-6, CU-04).
 *
 * Las coordenadas de referencia son las de la Universidad de Córdoba,
 * 8.78778 / -75.8578, que están en la constante del modelo.
 */
class DistanciaYMapaTest extends TestCase
{
    use RefreshDatabase;

    private const UNICORDOBA = [8.78778, -75.8578];

    private function alojamiento(?float $lat, ?float $lng, array $extra = []): Alojamiento
    {
        return Alojamiento::factory()
            ->for(User::factory()->arrendador())
            ->create(array_merge(['lat' => $lat, 'lng' => $lng], $extra));
    }

    /** Un punto desplazado $km kilómetros al norte de la universidad. */
    private function aKilometros(float $km): array
    {
        return [self::UNICORDOBA[0] + $km / 111.045, self::UNICORDOBA[1]];
    }

    /* ===================== El cálculo ===================== */

    public function test_la_distancia_de_un_punto_a_si_mismo_es_cero(): void
    {
        $a = $this->alojamiento(self::UNICORDOBA[0], self::UNICORDOBA[1], [
            'universidad_referencia' => 'Universidad de Córdoba',
        ]);

        $this->assertSame(0.0, $a->distanciaAUniversidad());
    }

    public function test_un_grado_de_latitud_son_unos_ciento_once_kilometros(): void
    {
        $a = $this->alojamiento(self::UNICORDOBA[0] + 1, self::UNICORDOBA[1], [
            'universidad_referencia' => 'Universidad de Córdoba',
        ]);

        // El meridiano mide 111,19 km por grado. Se admite medio kilómetro
        // de margen: lo que se comprueba es que la fórmula es la correcta,
        // no el radio exacto de la Tierra.
        $this->assertEqualsWithDelta(111.2, $a->distanciaAUniversidad(), 0.5);
    }

    public function test_sin_coordenadas_no_hay_distancia_en_vez_de_cero(): void
    {
        $a = $this->alojamiento(null, null);

        $this->assertNull($a->distanciaAUniversidad());
    }

    public function test_sin_coordenadas_de_la_universidad_no_hay_distancia(): void
    {
        // La UNAD está en la lista pero sin coordenadas comprobadas: más
        // vale no ofrecer la distancia que inventarla.
        $a = $this->alojamiento(8.75, -75.88, [
            'universidad_referencia' => 'UNAD — CEAD Montería',
        ]);

        $this->assertNull($a->distanciaAUniversidad());
    }

    /* ===================== El filtro ===================== */

    public function test_el_filtro_deja_fuera_lo_que_esta_mas_lejos_del_radio(): void
    {
        [$latCerca, $lngCerca] = $this->aKilometros(1.0);
        [$latLejos, $lngLejos] = $this->aKilometros(8.0);

        $cerca = $this->alojamiento($latCerca, $lngCerca, ['title' => 'A un kilómetro de la uni']);
        $lejos = $this->alojamiento($latLejos, $lngLejos, ['title' => 'A ocho kilómetros de la uni']);

        $ids = Alojamiento::query()
            ->disponibles()
            ->cercaDe(self::UNICORDOBA[0], self::UNICORDOBA[1], 2)
            ->pluck('id');

        $this->assertTrue($ids->contains($cerca->id));
        $this->assertFalse($ids->contains($lejos->id));
    }

    public function test_el_filtro_ignora_las_publicaciones_sin_coordenadas(): void
    {
        $sinPunto = $this->alojamiento(null, null);

        $ids = Alojamiento::query()
            ->cercaDe(self::UNICORDOBA[0], self::UNICORDOBA[1], 50)
            ->pluck('id');

        $this->assertFalse($ids->contains($sinPunto->id));
    }

    public function test_los_resultados_salen_ordenados_de_mas_cerca_a_mas_lejos(): void
    {
        foreach ([3.0, 0.5, 1.5] as $km) {
            [$lat, $lng] = $this->aKilometros($km);
            $this->alojamiento($lat, $lng, ['title' => "A {$km} km de la universidad"]);
        }

        $distancias = Alojamiento::query()
            ->cercaDe(self::UNICORDOBA[0], self::UNICORDOBA[1], 5)
            ->get()
            ->pluck('distancia_km')
            ->all();

        $ordenadas = $distancias;
        sort($ordenadas);

        $this->assertSame($ordenadas, $distancias);
    }

    public function test_el_buscador_aplica_el_filtro_por_universidad_y_kilometros(): void
    {
        [$latCerca, $lngCerca] = $this->aKilometros(1.0);
        [$latLejos, $lngLejos] = $this->aKilometros(9.0);

        $this->alojamiento($latCerca, $lngCerca, ['title' => 'Habitación junto al campus norte']);
        $this->alojamiento($latLejos, $lngLejos, ['title' => 'Habitación en la otra punta de la ciudad']);

        $this->get(route('alojamientos.index', [
            'universidad' => 'Universidad de Córdoba',
            'km'          => 2,
        ]))
            ->assertOk()
            ->assertSee('Habitación junto al campus norte')
            ->assertDontSee('Habitación en la otra punta de la ciudad');
    }

    public function test_una_universidad_inventada_no_filtra_nada(): void
    {
        // Si bastara con mandar cualquier nombre, el filtro podría quedarse
        // sin coordenadas y la consulta reventaría.
        $a = $this->alojamiento(8.75, -75.88, ['title' => 'Estudio en el centro de Montería']);

        $this->get(route('alojamientos.index', [
            'universidad' => 'Universidad de Hogwarts',
            'km'          => 1,
        ]))
            ->assertOk()
            ->assertSee('Estudio en el centro de Montería');

        $this->assertNotNull($a->fresh());
    }

    /* ===================== El mapa ===================== */

    public function test_los_archivos_de_leaflet_estan_en_el_proyecto(): void
    {
        /*
         * Esta prueba existe por un fallo real: la primera versión cargaba
         * Leaflet de un CDN con un hash «integrity» equivocado. El
         * navegador rechazaba el archivo y el mapa salía como un recuadro
         * vacío, sin ningún error en la página. Las pruebas pasaban porque
         * solo miraban que la etiqueta <script> estuviera escrita.
         *
         * Comprobar que el archivo existe de verdad es lo que habría
         * cazado aquello.
         */
        foreach ([
            'assets/vendor/leaflet/leaflet.js',
            'assets/vendor/leaflet/leaflet.css',
            'assets/vendor/leaflet/images/marker-icon.png',
            'assets/vendor/leaflet/images/marker-shadow.png',
            'assets/js/mapa.js',
            'assets/js/selector-mapa.js',
        ] as $archivo) {
            $this->assertFileExists(public_path($archivo));
            $this->assertGreaterThan(0, filesize(public_path($archivo)), "{$archivo} está vacío");
        }

        // Que sea Leaflet y no un archivo cualquiera con ese nombre.
        $this->assertStringContainsString(
            'Leaflet',
            file_get_contents(public_path('assets/vendor/leaflet/leaflet.js')),
        );
    }

    public function test_el_mapa_no_depende_de_ningun_dominio_externo(): void
    {
        // Salvo los mosaicos de OpenStreetMap, que por definición vienen de
        // fuera. Todo lo demás debe salir del propio proyecto: un CDN que
        // se cae o que un bloqueador corta deja el mapa en blanco.
        $a = $this->alojamiento(8.75, -75.88);

        $html = $this->get(route('alojamientos.index'))->assertOk()->getContent();

        foreach (['cdnjs.cloudflare.com', 'unpkg.com', 'cdn.jsdelivr.net'] as $cdn) {
            $this->assertStringNotContainsString($cdn, $html);
        }
    }

    public function test_el_buscador_pinta_el_mapa_con_los_puntos_de_la_pagina(): void
    {
        $a = $this->alojamiento(8.75, -75.88, ['title' => 'Apartaestudio con mapa']);

        $respuesta = $this->get(route('alojamientos.index'))->assertOk();

        $respuesta->assertSee('hb-mapa-lienzo', false);
        $respuesta->assertSee('assets/vendor/leaflet/leaflet.js', false);
        $respuesta->assertSee('assets/js/mapa.js', false);
        // El punto va en el atributo data-puntos, escapado como JSON.
        $respuesta->assertSee('Apartaestudio con mapa');
        $this->assertStringContainsString('8.75', $respuesta->getContent());
    }

    public function test_el_mapa_trae_los_botones_de_ampliar_y_ocultar(): void
    {
        $this->alojamiento(8.75, -75.88);

        $respuesta = $this->get(route('alojamientos.index'))->assertOk();

        $respuesta->assertSee('data-mapa-tamano', false);
        $respuesta->assertSee('data-mapa-ver', false);
        $respuesta->assertSee('Ampliar');
        $respuesta->assertSee('Ocultar');
    }

    public function test_la_barra_del_mapa_dice_cuantos_alojamientos_pinta(): void
    {
        $this->alojamiento(8.75, -75.88);
        $this->alojamiento(8.76, -75.89);
        // Este no tiene punto, así que no debe contarse.
        $this->alojamiento(null, null);

        $this->get(route('alojamientos.index'))
            ->assertOk()
            ->assertSee('2 alojamientos en el mapa');
    }

    public function test_un_solo_punto_se_anuncia_en_singular(): void
    {
        $this->alojamiento(8.75, -75.88);

        $this->get(route('alojamientos.index'))
            ->assertOk()
            ->assertSee('1 alojamiento en el mapa');
    }

    public function test_sin_publicaciones_con_coordenadas_no_se_carga_el_mapa(): void
    {
        $this->alojamiento(null, null, ['title' => 'Habitación sin punto en el mapa']);

        $this->get(route('alojamientos.index'))
            ->assertOk()
            ->assertSee('Habitación sin punto en el mapa')
            ->assertDontSee('hb-mapa-lienzo', false)
            ->assertDontSee('assets/vendor/leaflet/leaflet.js', false);
    }

    public function test_la_ficha_del_alojamiento_trae_su_propio_mapa(): void
    {
        $a = $this->alojamiento(8.75, -75.88, ['title' => 'Apartaestudio con mapa en la ficha']);

        $this->get(route('alojamientos.show', $a))
            ->assertOk()
            ->assertSee('hb-mapa-lienzo', false)
            ->assertSee('assets/vendor/leaflet/leaflet.js', false);
    }

    public function test_la_ficha_sin_coordenadas_no_pinta_mapa_pero_sigue_abriendo(): void
    {
        $a = $this->alojamiento(null, null, ['title' => 'Habitación sin punto marcado']);

        $this->get(route('alojamientos.show', $a))
            ->assertOk()
            ->assertSee('Habitación sin punto marcado')
            ->assertDontSee('hb-mapa-lienzo', false);
    }

    public function test_la_ficha_dice_a_cuantos_kilometros_esta_de_la_universidad(): void
    {
        [$lat, $lng] = $this->aKilometros(2.0);

        $a = $this->alojamiento($lat, $lng, [
            'universidad_referencia' => 'Universidad de Córdoba',
        ]);

        $this->get(route('alojamientos.show', $a))
            ->assertOk()
            ->assertSee('2 km')
            ->assertSee('en línea recta', false);
    }

    public function test_el_punto_del_mapa_lleva_titulo_precio_y_enlace(): void
    {
        $a = $this->alojamiento(8.75, -75.88);

        $punto = $a->puntoMapa();

        $this->assertSame(8.75, $punto['lat']);
        $this->assertSame(-75.88, $punto['lng']);
        $this->assertSame($a->title, $punto['titulo']);
        $this->assertSame($a->precioFormateado(), $punto['precio']);
        $this->assertSame(route('alojamientos.show', $a), $punto['url']);
    }

    public function test_sin_coordenadas_no_hay_punto_que_pintar(): void
    {
        $this->assertNull($this->alojamiento(null, null)->puntoMapa());
    }
}
