<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Aliado;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Publicidad de negocios locales, vendida directamente.
 *
 * Buena parte de estas pruebas comprueban dónde NO aparece el anuncio y
 * qué NO se carga con él. En una plataforma donde el producto es la
 * confianza, eso importa más que el anuncio en sí.
 */
class AliadoTest extends TestCase
{
    use RefreshDatabase;

    /* ===================== Qué se muestra ===================== */

    public function test_un_aliado_vigente_sale_en_el_buscador(): void
    {
        Aliado::factory()->create(['nombre' => 'Almuerzos Doña Rosa']);

        $this->get(route('alojamientos.index'))
            ->assertOk()
            ->assertSee('Almuerzos Doña Rosa');
    }

    public function test_los_que_estan_fuera_de_fecha_o_apagados_no_salen(): void
    {
        Aliado::factory()->vencido()->create(['nombre' => 'Pagó el semestre pasado']);
        Aliado::factory()->futuro()->create(['nombre' => 'Pagó por adelantado']);
        Aliado::factory()->apagado()->create(['nombre' => 'Apagado a mano']);

        $respuesta = $this->get(route('alojamientos.index'))->assertOk();

        $respuesta->assertDontSee('Pagó el semestre pasado');
        $respuesta->assertDontSee('Pagó por adelantado');
        $respuesta->assertDontSee('Apagado a mano');
    }

    public function test_sin_aliados_no_se_pinta_el_bloque(): void
    {
        $this->get(route('alojamientos.index'))
            ->assertOk()
            ->assertDontSee('hb-aliados', false);
    }

    /* ===================== Dónde NO aparece ===================== */

    public function test_el_anuncio_va_rotulado_como_publicidad(): void
    {
        /*
         * No «recomendados» ni «sugerencias». Si hay que disimular lo que
         * es, no debería estar ahí.
         */
        Aliado::factory()->create(['nombre' => 'Lavandería del barrio']);

        $this->get(route('alojamientos.index'))
            ->assertOk()
            ->assertSee('Publicidad');
    }

    public function test_el_anuncio_no_se_cuela_entre_los_resultados(): void
    {
        // Un anuncio con forma de tarjeta de alojamiento es un engaño, y
        // en una página de vivienda además un vector de estafa.
        Aliado::factory()->create(['nombre' => 'Colchones Montería']);
        Alojamiento::factory()->create();

        $html = $this->get(route('alojamientos.index'))->assertOk()->getContent();

        // Primero: que el anuncio SÍ esté en la página. Sin esto la
        // prueba pasaría igual si el bloque no se pintara en absoluto,
        // que es la forma más fácil de que una prueba mienta.
        $this->assertStringContainsString('Colchones Montería', $html);

        $inicioRejilla = mb_strpos($html, '<div class="hb-grid">');
        $this->assertNotFalse($inicioRejilla, 'No encontré la rejilla de resultados.');

        $this->assertStringNotContainsString(
            'Colchones Montería',
            mb_substr($html, $inicioRejilla),
            'La publicidad quedó dentro de la rejilla de resultados.',
        );
    }

    public function test_no_hay_publicidad_en_la_ficha_del_alojamiento_ni_en_el_chat(): void
    {
        /*
         * Ahí la persona está decidiendo dónde va a vivir o hablando con
         * el arrendador. Interrumpir eso para vender un almuerzo es ganar
         * mil pesos y perder la confianza.
         */
        Aliado::factory()->create(['nombre' => 'Trasteos Rápidos']);

        $alojamiento = Alojamiento::factory()->create();
        $estudiante  = User::factory()->create();

        $this->get(route('alojamientos.show', $alojamiento))
            ->assertOk()
            ->assertDontSee('Trasteos Rápidos');

        $this->actingAs($estudiante)
            ->get(route('mensajes.index'))
            ->assertOk()
            ->assertDontSee('Trasteos Rápidos');
    }

    public function test_no_se_carga_ningun_script_de_terceros_con_la_publicidad(): void
    {
        Aliado::factory()->create();

        $html = $this->get(route('alojamientos.index'))->assertOk()->getContent();

        foreach ([
            'googlesyndication', 'googletagmanager', 'google-analytics',
            'doubleclick', 'facebook.net', 'adsbygoogle',
        ] as $rastreador) {
            $this->assertStringNotContainsString($rastreador, $html);
        }
    }

    public function test_el_enlace_va_marcado_como_pagado(): void
    {
        // rel="sponsored" es lo que corresponde a un enlace pagado y evita
        // que el buscador lo lea como una recomendación nuestra.
        Aliado::factory()->create();

        $this->get(route('alojamientos.index'))
            ->assertOk()
            ->assertSee('rel="sponsored nofollow noopener noreferrer"', false);
    }

    /* ===================== Los clics ===================== */

    public function test_el_clic_se_cuenta_y_lleva_al_sitio_del_aliado(): void
    {
        $aliado = Aliado::factory()->create(['url' => 'https://ejemplo.test/promo']);

        $this->get(route('aliados.salir', $aliado))
            ->assertRedirect('https://ejemplo.test/promo');

        $this->assertSame(1, $aliado->fresh()->clics);
    }

    public function test_no_se_guarda_quien_hizo_clic(): void
    {
        /*
         * El aliado sabe cuánta gente entró; no sabe quién. Solo hay un
         * contador en su fila: ninguna columna de usuario, IP ni fecha
         * por visita.
         */
        $aliado = Aliado::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('aliados.salir', $aliado));

        $columnas = array_keys($aliado->fresh()->getAttributes());

        foreach (['user_id', 'ip', 'visitante', 'cookie'] as $prohibida) {
            $this->assertNotContains($prohibida, $columnas);
        }

        $this->assertSame(1, $aliado->fresh()->clics);
    }

    public function test_un_aliado_vencido_ya_no_redirige(): void
    {
        // Si no se puede mostrar, su enlace tampoco debe seguir sirviendo.
        $aliado = Aliado::factory()->vencido()->create();

        $this->get(route('aliados.salir', $aliado))->assertNotFound();
        $this->assertSame(0, $aliado->fresh()->clics);
    }

    /* ===================== El panel ===================== */

    public function test_el_admin_crea_un_aliado(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.aliados.store'), [
                'nombre'      => 'Papelería La Esquina',
                'descripcion' => 'Fotocopias e impresiones frente a la universidad',
                'url'         => 'https://papeleria.test',
                'desde'       => today()->toDateString(),
                'hasta'       => today()->addMonth()->toDateString(),
                'monto'       => 80000,
                'orden'       => 0,
                'activo'      => 1,
                'logo'        => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertRedirect(route('admin.aliados.index'));

        $aliado = Aliado::firstWhere('nombre', 'Papelería La Esquina');

        $this->assertNotNull($aliado);
        $this->assertNotNull($aliado->logo_path);
        Storage::disk('public')->assertExists($aliado->logo_path);
        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::ALIADO_GUARDADO]);
    }

    public function test_un_enlace_sin_https_no_se_acepta(): void
    {
        // Enlazar a http desde una página segura hace que el navegador
        // avise, y eso queda fatal justo donde vendes confianza.
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.aliados.store'), [
                'nombre'      => 'Negocio inseguro',
                'descripcion' => 'Prueba',
                'url'         => 'http://sincifrar.test',
                'desde'       => today()->toDateString(),
                'hasta'       => today()->addMonth()->toDateString(),
                'monto'       => 0,
                'orden'       => 0,
            ])
            ->assertSessionHasErrors('url');

        $this->assertSame(0, Aliado::count());
    }

    public function test_la_fecha_de_fin_no_puede_ser_anterior_a_la_de_inicio(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.aliados.store'), [
                'nombre'      => 'Fechas al revés',
                'descripcion' => 'Prueba',
                'url'         => 'https://ejemplo.test',
                'desde'       => today()->toDateString(),
                'hasta'       => today()->subWeek()->toDateString(),
                'monto'       => 0,
                'orden'       => 0,
            ])
            ->assertSessionHasErrors('hasta');
    }

    public function test_al_borrar_se_elimina_tambien_el_logo_del_disco(): void
    {
        Storage::fake('public');

        $aliado = Aliado::factory()->create([
            'logo_path' => UploadedFile::fake()->image('logo.png')->store('aliados', 'public'),
        ]);

        Storage::disk('public')->assertExists($aliado->logo_path);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.aliados.destroy', $aliado))
            ->assertRedirect();

        Storage::disk('public')->assertMissing($aliado->logo_path);
        $this->assertSame(0, Aliado::count());
    }

    public function test_un_usuario_normal_no_administra_aliados(): void
    {
        $aliado = Aliado::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.aliados.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.aliados.destroy', $aliado))
            ->assertForbidden();

        $this->assertSame(1, Aliado::count());
    }

    public function test_sin_logo_se_muestran_las_iniciales(): void
    {
        $aliado = Aliado::factory()->create(['nombre' => 'Lavandería Central', 'logo_path' => null]);

        $this->assertNull($aliado->logoUrl());
        $this->assertSame('LC', $aliado->iniciales());
    }
}
