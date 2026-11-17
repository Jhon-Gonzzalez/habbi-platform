<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Campana;
use App\Models\SolicitudCampana;
use App\Models\TarifaCampana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SolicitudCampanaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function tarifa(int $precio = 20000, int $dias = 15): TarifaCampana
    {
        return TarifaCampana::create([
            'nombre' => 'Quincena', 'dias' => $dias, 'precio' => $precio,
            'activa' => true, 'destacada' => true, 'orden' => 1,
        ]);
    }

    /** @return array{0: Alojamiento, 1: TarifaCampana} */
    private function escenario(): array
    {
        Storage::fake('private');

        return [Alojamiento::factory()->create(), $this->tarifa()];
    }

    private function datos(array $extra = []): array
    {
        return array_merge([
            'medio'       => 'Nequi',
            'referencia'  => 'M12345',
            'comprobante' => UploadedFile::fake()->image('pago.jpg'),
        ], $extra);
    }

    /* ===================== Pedirla ===================== */

    public function test_el_arrendador_solicita_una_campana(): void
    {
        [$alojamiento, $tarifa] = $this->escenario();

        $this->actingAs($alojamiento->user)
            ->post(route('alojamientos.campana.solicitar', $alojamiento),
                $this->datos(['tarifa_id' => $tarifa->id]))
            ->assertRedirect();

        $solicitud = SolicitudCampana::sole();

        $this->assertSame(SolicitudCampana::PENDIENTE, $solicitud->estado);
        $this->assertSame($alojamiento->user_id, $solicitud->solicitada_por);
        Storage::disk('private')->assertExists($solicitud->comprobante);
    }

    public function test_pedirla_no_activa_la_campana(): void
    {
        /*
         * Lo único que activa es que un administrador verifique el pago.
         * Si bastara con pedirla, cualquiera se destacaría gratis.
         */
        [$alojamiento, $tarifa] = $this->escenario();

        $this->actingAs($alojamiento->user)
            ->post(route('alojamientos.campana.solicitar', $alojamiento),
                $this->datos(['tarifa_id' => $tarifa->id]));

        $this->assertFalse($alojamiento->refresh()->tieneCampanaActiva());
        $this->assertDatabaseCount('campanas', 0);
    }

    public function test_el_comprobante_es_obligatorio(): void
    {
        // Sin comprobante no hay nada que verificar y la solicitud se
        // queda parada esperando un correo que quizá nunca llega.
        [$alojamiento, $tarifa] = $this->escenario();

        $this->actingAs($alojamiento->user)
            ->post(route('alojamientos.campana.solicitar', $alojamiento),
                ['tarifa_id' => $tarifa->id, 'medio' => 'Nequi'])
            ->assertSessionHasErrors('comprobante');

        $this->assertDatabaseCount('solicitudes_campana', 0);
    }

    public function test_el_precio_queda_congelado_aunque_suba_la_tarifa(): void
    {
        // Una solicitud de ayer tiene que seguir diciendo lo que se acordó
        // ayer, no lo que cuesta hoy.
        [$alojamiento, $tarifa] = $this->escenario();

        $this->actingAs($alojamiento->user)
            ->post(route('alojamientos.campana.solicitar', $alojamiento),
                $this->datos(['tarifa_id' => $tarifa->id]));

        $tarifa->update(['precio' => 99000, 'nombre' => 'Quincena cara']);

        $solicitud = SolicitudCampana::sole();

        $this->assertSame(20000, $solicitud->monto);
        $this->assertSame('Quincena', $solicitud->nombre_tarifa);
    }

    public function test_no_se_puede_pedir_dos_veces_a_la_vez(): void
    {
        [$alojamiento, $tarifa] = $this->escenario();

        $this->actingAs($alojamiento->user)
            ->post(route('alojamientos.campana.solicitar', $alojamiento),
                $this->datos(['tarifa_id' => $tarifa->id]));

        $this->actingAs($alojamiento->user)
            ->post(route('alojamientos.campana.solicitar', $alojamiento),
                $this->datos(['tarifa_id' => $tarifa->id]));

        $this->assertDatabaseCount('solicitudes_campana', 1);
    }

    public function test_una_publicacion_que_no_se_ve_no_puede_pedir_campana(): void
    {
        // Cobrar por subir de posición algo que nadie puede ver sería
        // cobrar por nada.
        Storage::fake('private');
        $alojamiento = Alojamiento::factory()->pendiente()->create();
        $tarifa      = $this->tarifa();

        $this->actingAs($alojamiento->user)
            ->post(route('alojamientos.campana.solicitar', $alojamiento),
                $this->datos(['tarifa_id' => $tarifa->id]))
            ->assertForbidden();
    }

    public function test_nadie_pide_campana_para_una_publicacion_ajena(): void
    {
        [$alojamiento, $tarifa] = $this->escenario();

        $this->actingAs(User::factory()->arrendador()->create())
            ->post(route('alojamientos.campana.solicitar', $alojamiento),
                $this->datos(['tarifa_id' => $tarifa->id]))
            ->assertForbidden();
    }

    /* ===================== Resolverla ===================== */

    private function pedida(): SolicitudCampana
    {
        [$alojamiento, $tarifa] = $this->escenario();

        $this->actingAs($alojamiento->user)
            ->post(route('alojamientos.campana.solicitar', $alojamiento),
                $this->datos(['tarifa_id' => $tarifa->id]));

        return SolicitudCampana::sole();
    }

    public function test_al_aprobarla_se_activa_la_campana_y_queda_el_cobro(): void
    {
        $solicitud = $this->pedida();

        $this->actingAs($this->admin())
            ->post(route('admin.campanas.aprobar', $solicitud))
            ->assertRedirect();

        $solicitud->refresh();

        $this->assertSame(SolicitudCampana::APROBADA, $solicitud->estado);
        $this->assertTrue($solicitud->alojamiento->refresh()->tieneCampanaActiva());

        $cobro = Campana::sole();
        $this->assertSame(20000, $cobro->monto);
        $this->assertSame(15, $cobro->dias);
    }

    public function test_aprobar_dos_veces_no_regala_dias(): void
    {
        $solicitud = $this->pedida();
        $admin     = $this->admin();

        $this->actingAs($admin)->post(route('admin.campanas.aprobar', $solicitud));
        $this->actingAs($admin)->post(route('admin.campanas.aprobar', $solicitud));

        $this->assertDatabaseCount('campanas', 1);
    }

    public function test_al_rechazarla_no_se_activa_nada_y_queda_el_motivo(): void
    {
        $solicitud = $this->pedida();

        $this->actingAs($this->admin())
            ->post(route('admin.campanas.rechazar', $solicitud),
                ['respuesta' => 'El comprobante no corresponde al valor del plan.'])
            ->assertRedirect();

        $solicitud->refresh();

        $this->assertSame(SolicitudCampana::RECHAZADA, $solicitud->estado);
        $this->assertStringContainsString('no corresponde', $solicitud->respuesta);
        $this->assertFalse($solicitud->alojamiento->refresh()->tieneCampanaActiva());
        $this->assertDatabaseCount('campanas', 0);
    }

    public function test_rechazar_sin_motivo_no_se_permite(): void
    {
        // Una solicitud rechazada sin explicación deja al arrendador
        // escribiendo para preguntar qué pasó.
        $solicitud = $this->pedida();

        $this->actingAs($this->admin())
            ->post(route('admin.campanas.rechazar', $solicitud), ['respuesta' => ''])
            ->assertSessionHasErrors('respuesta');

        $this->assertTrue($solicitud->refresh()->estaPendiente());
    }

    public function test_un_arrendador_no_puede_aprobarse_su_propia_campana(): void
    {
        $solicitud = $this->pedida();

        $this->actingAs($solicitud->alojamiento->user)
            ->post(route('admin.campanas.aprobar', $solicitud))
            ->assertForbidden();

        $this->assertTrue($solicitud->refresh()->estaPendiente());
    }

    /* ===================== El comprobante ===================== */

    public function test_el_comprobante_lo_ven_su_dueno_y_el_admin(): void
    {
        $solicitud = $this->pedida();

        $this->actingAs($solicitud->alojamiento->user)
            ->get(route('campanas.comprobante', $solicitud))->assertOk();

        $this->actingAs($this->admin())
            ->get(route('campanas.comprobante', $solicitud))->assertOk();
    }

    public function test_el_comprobante_no_lo_ve_un_tercero(): void
    {
        // Es un documento bancario de una persona.
        $solicitud = $this->pedida();

        $this->actingAs(User::factory()->create())
            ->get(route('campanas.comprobante', $solicitud))
            ->assertForbidden();
    }

    public function test_el_comprobante_no_queda_en_una_url_publica(): void
    {
        $solicitud = $this->pedida();

        $this->assertStringStartsWith('comprobantes/', $solicitud->comprobante);
        $this->assertFalse(
            file_exists(public_path('storage/' . $solicitud->comprobante)),
            'El comprobante no puede quedar accesible desde public/.',
        );
    }
}
