<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_invitado_no_puede_reportar(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->get(route('reportes.create', $alojamiento))->assertRedirect(route('login'));
    }

    public function test_un_estudiante_reporta_una_publicacion(): void
    {
        $estudiante  = User::factory()->create();
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs($estudiante)
            ->post(route('reportes.store', $alojamiento), [
                'motivo'  => 'estafa',
                'detalle' => 'Me pidieron 300 mil por adelantado antes de mostrarme el cuarto.',
            ])
            ->assertRedirect(route('alojamientos.show', $alojamiento))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reportes', [
            'user_id'        => $estudiante->id,
            'alojamiento_id' => $alojamiento->id,
            'motivo'         => 'estafa',
            'estado'         => Reporte::ESTADO_ABIERTO,
        ]);
    }

    public function test_reportar_no_oculta_la_publicacion(): void
    {
        // Un reporte es una señal, no una sentencia: si bastara con reportar,
        // cualquiera podría tumbar los anuncios de la competencia.
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('reportes.store', $alojamiento), [
            'motivo' => 'precio',
        ]);

        $this->assertTrue($alojamiento->fresh()->is_active);
        $this->get(route('alojamientos.show', $alojamiento))->assertOk();
    }

    public function test_el_motivo_otro_exige_explicacion(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reportes.store', $alojamiento), ['motivo' => 'otro'])
            ->assertSessionHasErrors('detalle');
    }

    public function test_rechaza_un_motivo_que_no_esta_en_la_lista(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reportes.store', $alojamiento), ['motivo' => 'no-me-gusta'])
            ->assertSessionHasErrors('motivo');
    }

    public function test_nadie_reporta_su_propia_publicacion(): void
    {
        $arrendador  = User::factory()->arrendador()->create();
        $alojamiento = Alojamiento::factory()->for($arrendador)->create();

        $this->actingAs($arrendador)->post(route('reportes.store', $alojamiento), [
            'motivo' => 'precio',
        ]);

        $this->assertDatabaseCount('reportes', 0);
    }

    public function test_reportar_dos_veces_no_crea_dos_filas(): void
    {
        $estudiante  = User::factory()->create();
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs($estudiante)->post(route('reportes.store', $alojamiento), ['motivo' => 'precio']);
        $this->actingAs($estudiante)->post(route('reportes.store', $alojamiento), ['motivo' => 'fotos']);

        $this->assertDatabaseCount('reportes', 1);
        // El primero sigue abierto, así que el segundo no lo sobreescribe.
        $this->assertDatabaseHas('reportes', ['motivo' => 'precio']);
    }

    public function test_tras_cerrarse_se_puede_volver_a_reportar(): void
    {
        $estudiante  = User::factory()->create();
        $alojamiento = Alojamiento::factory()->create();

        $reporte = Reporte::factory()->descartado()->create([
            'user_id'        => $estudiante->id,
            'alojamiento_id' => $alojamiento->id,
            'motivo'         => 'precio',
        ]);

        $this->actingAs($estudiante)->post(route('reportes.store', $alojamiento), [
            'motivo'  => 'estafa',
            'detalle' => 'Volvió a pasar: ahora me pidieron plata por WhatsApp.',
        ]);

        // Se reabre la misma fila en vez de crear otra.
        $this->assertDatabaseCount('reportes', 1);
        $reporte->refresh();
        $this->assertSame('estafa', $reporte->motivo);
        $this->assertSame(Reporte::ESTADO_ABIERTO, $reporte->estado);
        $this->assertNull($reporte->revisado_por);
    }

    /* ===================== Panel de administración ===================== */

    public function test_un_usuario_normal_no_entra_al_panel_de_reportes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.reportes.index'))
            ->assertForbidden();
    }

    public function test_el_admin_ve_los_reportes_abiertos(): void
    {
        Reporte::factory()->create(['detalle' => 'La dirección no existe, fui y no hay nada.']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.reportes.index'))
            ->assertOk()
            ->assertSee('La dirección no existe, fui y no hay nada.');
    }

    public function test_el_admin_cierra_un_reporte_y_queda_registrado_quien_fue(): void
    {
        $admin   = User::factory()->admin()->create();
        $reporte = Reporte::factory()->create();

        $this->actingAs($admin)->post(route('admin.reportes.resolver', $reporte), [
            'estado'       => Reporte::ESTADO_ATENDIDO,
            'nota_interna' => 'Hablé con el arrendador y corrigió el precio.',
        ])->assertSessionHasNoErrors();

        $reporte->refresh();
        $this->assertSame(Reporte::ESTADO_ATENDIDO, $reporte->estado);
        $this->assertSame($admin->id, $reporte->revisado_por);
        $this->assertNotNull($reporte->revisado_en);
    }

    public function test_cerrar_un_reporte_no_toca_la_publicacion(): void
    {
        // Pausar o borrar el anuncio es una decisión aparte, a propósito.
        $reporte = Reporte::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.reportes.resolver', $reporte), [
                'estado' => Reporte::ESTADO_ATENDIDO,
            ]);

        $this->assertTrue($reporte->alojamiento->fresh()->is_active);
    }

    public function test_el_admin_no_puede_inventarse_un_estado(): void
    {
        $reporte = Reporte::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.reportes.resolver', $reporte), ['estado' => 'archivado'])
            ->assertSessionHasErrors('estado');
    }
}
