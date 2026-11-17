<?php

namespace Tests\Feature;

use App\Support\Recurso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecursoTest extends TestCase
{
    // La última prueba pide la portada, y la portada consulta alojamientos.
    use RefreshDatabase;

    public function test_el_css_sale_con_version(): void
    {
        /*
         * Sin versión, el navegador se queda con la hoja de estilos vieja
         * durante días: el despliegue llega al servidor pero no a quien ya
         * visitó la página, y parece que el arreglo de diseño no funcionó.
         */
        $url = Recurso::url('assets/css/habbi.css');

        $this->assertMatchesRegularExpression('/habbi\.css\?v=\d+$/', $url);
    }

    public function test_la_version_cambia_cuando_cambia_el_archivo(): void
    {
        $ruta = public_path('assets/css/prueba-version.css');
        file_put_contents($ruta, 'a{}');
        touch($ruta, 1000000000);
        $antes = Recurso::url('assets/css/prueba-version.css');

        clearstatcache(true, $ruta);
        touch($ruta, 2000000000);
        clearstatcache(true, $ruta);
        $despues = Recurso::url('assets/css/prueba-version.css');

        unlink($ruta);

        $this->assertNotSame($antes, $despues);
    }

    public function test_un_archivo_que_no_existe_no_revienta(): void
    {
        // Más vale servir sin versión que dejar la página sin estilos.
        $url = Recurso::url('assets/css/no-existe-esto.css');

        $this->assertStringEndsWith('no-existe-esto.css', $url);
    }

    public function test_las_plantillas_piden_el_css_con_version(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/habbi\.css\?v=\d+/',
            $html,
            'El layout debe usar @recurso, no asset(), o el CSS se queda cacheado.',
        );
    }
}
