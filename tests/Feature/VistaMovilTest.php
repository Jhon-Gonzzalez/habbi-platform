<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo que sostiene la versión móvil.
 *
 * El acomodo visual se comprueba en un navegador, no aquí. Estas
 * pruebas fijan lo que SÍ depende del servidor y que un refactor podría
 * romper sin que nadie lo note: sobre todo, que la página siga siendo
 * usable sin JavaScript.
 */
class VistaMovilTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_filtros_llegan_abiertos_desde_el_servidor(): void
    {
        /*
         * Esta es la importante. El panel se pliega en móvil con
         * JavaScript, PARTIENDO de abierto. Si algún día alguien lo
         * manda cerrado desde el servidor para «ahorrarse el JS»,
         * quien tenga el JavaScript bloqueado o le falle la carga se
         * queda sin poder filtrar, y encima sin saber por qué.
         *
         * El peor caso tiene que seguir siendo «se ve abierto», que es
         * exactamente como estaba antes de que existiera el plegado.
         */
        $html = $this->get(route('alojamientos.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<details[^>]*\bid="filtros"[^>]*\bopen\b/',
            $html,
            'El panel de filtros debe llegar con «open»: sin JavaScript no habría forma de abrirlo.',
        );
    }

    public function test_el_resumen_de_filtros_dice_cuantos_hay_puestos(): void
    {
        // Plegado en el móvil, el número es lo único que le dice al
        // usuario que su búsqueda sigue filtrada.
        Alojamiento::factory()->create();

        $this->get(route('alojamientos.index', ['type' => 'Habitación', 'price_max' => 500000]))
            ->assertOk()
            ->assertSee('data-con-filtros', false)
            ->assertSee('Quitar los filtros');
    }

    public function test_sin_filtros_no_sale_ni_el_numero_ni_el_enlace_de_limpiar(): void
    {
        $this->get(route('alojamientos.index'))
            ->assertOk()
            ->assertDontSee('data-con-filtros', false)
            ->assertDontSee('Quitar los filtros');
    }

    public function test_el_buscador_se_puede_usar_entero_sin_javascript(): void
    {
        /*
         * Filtrar es un formulario GET normal: sin fetch, sin JSON y sin
         * depender de ningún script. Se comprueba enviando los mismos
         * parámetros que mandaría el formulario.
         */
        Alojamiento::factory()->create(['title' => 'Habitación barata en La Granja', 'price' => 300000]);
        Alojamiento::factory()->create(['title' => 'Apartamento caro en El Recreo', 'price' => 2000000]);

        $this->get(route('alojamientos.index', ['price_max' => 500000]))
            ->assertOk()
            ->assertSee('Habitación barata en La Granja')
            ->assertDontSee('Apartamento caro en El Recreo');
    }

    public function test_todas_las_tablas_pueden_desplazarse_en_horizontal(): void
    {
        /*
         * Una tabla sin su envoltorio se sale de la pantalla en el
         * teléfono y arrastra con ella toda la página. Es un fallo que
         * no se ve en el escritorio, así que conviene vigilarlo aquí.
         */
        // Recorrido recursivo de verdad: glob('views/**/*.blade.php') NO
        // baja por los subdirectorios en PHP, y con él esta prueba pasaba
        // aunque le quitaras el envoltorio a una tabla. Comprobado.
        $vistas = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        $sinEnvoltorio = [];

        foreach ($vistas as $vista) {
            if ($vista->isDir() || ! str_ends_with($vista->getFilename(), '.blade.php')) {
                continue;
            }

            $contenido = file_get_contents($vista->getPathname());

            if (! str_contains($contenido, '<table class="hb-table"')) {
                continue;
            }

            if (! str_contains($contenido, 'hb-table-wrap')) {
                $sinEnvoltorio[] = basename(dirname($vista->getPathname())) . '/' . $vista->getFilename();
            }
        }

        $this->assertSame([], $sinEnvoltorio,
            'Estas tablas no están dentro de .hb-table-wrap y se saldrán de la pantalla en el móvil.');
    }
}
