<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Rating;
use App\Models\User;
use App\Services\ReporteEstadisticas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** RF-11 y CU-08: informes con gráficas en pantalla y exportación a PDF. */
class InformeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /* ===================== Acceso ===================== */

    public function test_un_usuario_normal_no_entra_a_los_informes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.informes.index'))
            ->assertForbidden();
    }

    public function test_un_usuario_normal_tampoco_descarga_el_pdf(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.informes.pdf'))
            ->assertForbidden();
    }

    /* ===================== El rango de fechas ===================== */

    public function test_por_defecto_cubre_el_ultimo_mes(): void
    {
        $datos = ReporteEstadisticas::entre(null, null);

        $this->assertSame(30, $datos->dias());
    }

    public function test_respeta_el_rango_que_se_le_pide(): void
    {
        $datos = ReporteEstadisticas::entre('2026-03-01', '2026-03-31');

        $this->assertSame(31, $datos->dias());
        $this->assertSame('2026-03-01', $datos->desde->format('Y-m-d'));
    }

    public function test_si_las_fechas_vienen_al_reves_se_enderezan(): void
    {
        // Devolver un informe vacío parecería un error de la plataforma.
        $datos = ReporteEstadisticas::entre('2026-03-31', '2026-03-01');

        $this->assertSame('2026-03-01', $datos->desde->format('Y-m-d'));
        $this->assertSame('2026-03-31', $datos->hasta->format('Y-m-d'));
    }

    public function test_rechaza_una_fecha_que_no_es_una_fecha(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.informes.index', ['desde' => 'el mes pasado']))
            ->assertSessionHasErrors('desde');
    }

    /* ===================== Las cifras ===================== */

    public function test_cuenta_solo_lo_que_cae_dentro_del_periodo(): void
    {
        User::factory()->create(['created_at' => now()->subDays(3)]);    // dentro
        User::factory()->create(['created_at' => now()->subYear()]);     // fuera

        $u = ReporteEstadisticas::entre(null, null)->usuarios();

        // El de hace un año cuenta en el total, pero no como «nuevo».
        $this->assertSame(2, $u['total']);
        $this->assertSame(1, $u['nuevos']);
    }

    public function test_distingue_publicaciones_visibles_de_pendientes(): void
    {
        Alojamiento::factory()->count(3)->create();
        Alojamiento::factory()->pendiente()->count(2)->create();

        $p = ReporteEstadisticas::entre(null, null)->publicaciones();

        $this->assertSame(5, $p['total']);
        $this->assertSame(3, $p['activas']);
        $this->assertSame(2, $p['pendientes']);
    }

    public function test_la_serie_por_dia_rellena_los_dias_vacios(): void
    {
        // Sin rellenar, un hueco de tres días sin registros parecería un día
        // normal al pintar la gráfica.
        $serie = ReporteEstadisticas::entre('2026-03-01', '2026-03-10')->registrosPorDia();

        $this->assertCount(10, $serie);
        $this->assertSame(0, $serie->first());
    }

    /* ===================== La pantalla ===================== */

    public function test_la_pantalla_muestra_las_cifras(): void
    {
        Alojamiento::factory()->count(3)->create(['neighborhood' => 'La Granja']);
        Rating::factory()->count(2)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.informes.index'))
            ->assertOk()
            ->assertSee('Usuarios')
            ->assertSee('Publicaciones')
            ->assertSee('Oferta por barrio')
            ->assertSee('La Granja');
    }

    public function test_avisa_cuando_no_hay_datos_en_vez_de_pintar_una_grafica_vacia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.informes.index', ['desde' => '2020-01-01', 'hasta' => '2020-01-31']))
            ->assertOk()
            ->assertSee('No hubo registros nuevos en este periodo');
    }

    /* ===================== El PDF ===================== */

    public function test_descarga_un_pdf_de_verdad(): void
    {
        Alojamiento::factory()->count(2)->create();

        $r = $this->actingAs($this->admin())->get(route('admin.informes.pdf'));

        $r->assertOk()->assertHeader('content-type', 'application/pdf');

        // Los primeros bytes de un PDF son siempre los mismos. Se usa
        // getContent() y no streamedContent(): dompdf devuelve una respuesta
        // normal con el documento dentro, no una respuesta en flujo.
        $this->assertStringStartsWith('%PDF-', $r->getContent());
    }

    public function test_el_pdf_se_descarga_con_un_nombre_que_dice_el_periodo(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.informes.pdf', ['desde' => '2026-03-01', 'hasta' => '2026-03-31']))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=habbi-informe-20260301-20260331.pdf');
    }

    public function test_el_pdf_lleva_las_cifras_dentro_no_una_pagina_en_blanco(): void
    {
        // dompdf no ejecuta JavaScript: si las gráficas se hubieran hecho con
        // una librería de JS, el documento saldría con los huecos vacíos.
        Alojamiento::factory()->count(4)->create(['neighborhood' => 'Buenavista']);

        $pdf = $this->actingAs($this->admin())
            ->get(route('admin.informes.pdf'))
            ->getContent();

        $this->assertGreaterThan(5000, strlen($pdf), 'El PDF es sospechosamente pequeño.');
    }

    public function test_la_pantalla_y_el_pdf_usan_el_mismo_parcial(): void
    {
        /*
         * Si cada uno hiciera sus propias consultas acabarían enseñando
         * números distintos. Esto comprueba que ambos incluyen el mismo
         * archivo, que es lo que garantiza que no puedan discrepar.
         */
        foreach (['index', 'pdf'] as $vista) {
            $this->assertStringContainsString(
                "@include('admin.informes._cuerpo'",
                file_get_contents(resource_path("views/admin/informes/{$vista}.blade.php")),
                "La vista {$vista} no usa el parcial compartido.",
            );
        }
    }
}
