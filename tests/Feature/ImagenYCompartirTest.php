<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImagenYCompartirTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // El controlador escribe los PNG en storage/app/public/marcadores.
        foreach (glob(storage_path('app/public/marcadores/*.png')) as $f) {
            @unlink($f);
        }

        parent::tearDown();
    }

    /* ===================== La imagen de marcador ===================== */

    /**
     * Devuelve los bytes que sirvió la ruta.
     *
     * No vale $response->getContent(): una respuesta de archivo
     * (BinaryFileResponse) lo devuelve vacío, y comparar dos cadenas vacías
     * hace pasar cualquier test sin probar nada. Hay que leer el archivo.
     */
    private function bytesServidos(Alojamiento $a): string
    {
        $r = $this->get(route('alojamientos.marcador', $a));
        $r->assertOk();

        return file_get_contents($r->baseResponse->getFile()->getPathname());
    }

    public function test_devuelve_un_png_de_verdad(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->get(route('alojamientos.marcador', $alojamiento))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        // Los primeros bytes de un PNG son siempre los mismos.
        $this->assertStringStartsWith("\x89PNG", $this->bytesServidos($alojamiento));
    }

    public function test_es_publica_porque_la_piden_whatsapp_y_las_redes(): void
    {
        // Quien previsualiza un enlace compartido no tiene sesión. Si esto
        // exigiera login, los enlaces llegarían sin imagen.
        $this->get(route('alojamientos.marcador', Alojamiento::factory()->create()))
            ->assertOk();
    }

    public function test_dos_alojamientos_distintos_dan_imagenes_distintas(): void
    {
        // Antes todos los anuncios sin foto mostraban la misma portada del
        // sitio: veinte tarjetas idénticas parecen un error.
        $uno = Alojamiento::factory()->create(['type' => 'Habitación']);
        $dos = Alojamiento::factory()->create(['type' => 'Apartamento']);

        $this->assertNotSame(
            md5($this->bytesServidos($uno)),
            md5($this->bytesServidos($dos)),
        );
    }

    public function test_el_mismo_alojamiento_da_siempre_la_misma_imagen(): void
    {
        // Se pregunta al generador, no a la ruta: la ruta guarda el archivo
        // la primera vez y luego lo sirve, así que daría igual aunque el
        // dibujo no fuera determinista.
        $a         = Alojamiento::factory()->create();
        $generador = new \App\Services\ImagenMarcador();

        $this->assertSame(
            md5($generador->generar($a)),
            md5($generador->generar($a)),
        );
    }

    public function test_se_guarda_en_disco_para_no_regenerarla_en_cada_carga(): void
    {
        $a = Alojamiento::factory()->create();

        $this->get(route('alojamientos.marcador', $a))->assertOk();

        $this->assertFileExists(storage_path("app/public/marcadores/{$a->id}.png"));
    }

    public function test_la_portada_usa_el_marcador_cuando_no_hay_foto(): void
    {
        $a = Alojamiento::factory()->create(['cover_path' => null]);

        $this->assertSame(route('alojamientos.marcador', $a), $a->coverUrl());
    }

    /* ===================== Compartir el enlace ===================== */

    public function test_la_publicacion_trae_las_etiquetas_para_whatsapp(): void
    {
        $a = Alojamiento::factory()->create([
            'title'        => 'Cuarto grande en La Granja',
            'city'         => 'Montería',
            'neighborhood' => 'La Granja',
            'price'        => 450000,
        ]);

        $html = $this->get(route('alojamientos.show', $a))->assertOk()->getContent();

        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('Cuarto grande en La Granja', $html);
        $this->assertStringContainsString('property="og:image"', $html);

        // La descripción debe traer lo que decide a alguien: precio y zona.
        $this->assertMatchesRegularExpression(
            '/og:description" content="[^"]*450\.000[^"]*La Granja/u',
            $html,
        );
    }

    public function test_la_imagen_de_compartir_es_una_url_absoluta(): void
    {
        // WhatsApp y Facebook piden la imagen desde sus propios servidores:
        // una ruta relativa no les sirve.
        $a = Alojamiento::factory()->create();

        preg_match('/og:image" content="([^"]+)"/', $this->get(route('alojamientos.show', $a))->getContent(), $m);

        $this->assertNotEmpty($m);
        $this->assertStringStartsWith('http', $m[1]);
    }

    public function test_el_resto_del_sitio_tambien_trae_etiquetas(): void
    {
        $this->get(route('index'))
            ->assertOk()
            ->assertSee('og:site_name', false)
            ->assertSee('twitter:card', false);
    }

    /* ===================== Páginas de error ===================== */

    public function test_el_404_es_una_pagina_de_habbi_y_no_la_de_laravel(): void
    {
        $this->get('/esta-pagina-no-existe')
            ->assertNotFound()
            ->assertSee('Aquí no hay nada')
            ->assertSee('Buscar alojamiento');
    }

    public function test_el_403_explica_en_castellano(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.index'))
            ->assertForbidden()
            ->assertSee('Esta parte no es para ti');
    }

    public function test_un_alojamiento_que_no_existe_da_el_404_bonito(): void
    {
        $this->get('/alojamientos/999999')
            ->assertNotFound()
            ->assertSee('ya no esté publicado');
    }

    public function test_la_pagina_de_500_no_toca_la_base_de_datos(): void
    {
        // Si el 500 viene de que la base está caída, una página de error que
        // la consulte falla también y el visitante ve una pantalla en blanco.
        $fuente = file_get_contents(resource_path('views/errors/500.blade.php'));

        // Se quitan los comentarios de Blade antes de mirar: el de este
        // archivo menciona @auth justo para explicar por qué no se usa.
        $codigo = preg_replace('/\{\{--.*?--\}\}/s', '', $fuente);

        foreach (['@auth', 'session(', 'auth()', 'route('] as $prohibido) {
            $this->assertStringNotContainsString($prohibido, $codigo,
                "La página de error 500 usa «{$prohibido}», que necesita base de datos o sesión.");
        }
    }
}
