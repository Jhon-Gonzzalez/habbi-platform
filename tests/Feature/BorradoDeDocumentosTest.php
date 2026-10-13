<?php

namespace Tests\Feature;

use App\Models\SolicitudArrendador;
use App\Models\User;
use App\Services\VerificacionArrendadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Los documentos de verificación se borran al resolver la solicitud.
 *
 * No es un detalle: la política de privacidad del propio sitio promete
 * que «los documentos de verificación [se conservan] hasta que resolvamos
 * la solicitud». Durante un tiempo el método que los borraba existía pero
 * no lo llamaba nadie, así que la aplicación incumplía lo que prometía por
 * escrito. Estos tests están para que eso no vuelva a pasar sin avisar.
 */
class BorradoDeDocumentosTest extends TestCase
{
    use RefreshDatabase;

    private function solicitudConDocumentos(): SolicitudArrendador
    {
        Storage::fake('private');

        return app(VerificacionArrendadorService::class)->solicitar(
            User::factory()->create(),
            UploadedFile::fake()->image('cedula.jpg'),
            UploadedFile::fake()->image('recibo.jpg'),
            '3001234567',
            'Quiero arrendar la habitación de mi casa a estudiantes.',
        );
    }

    public function test_al_aprobar_desaparecen_del_disco(): void
    {
        $solicitud = $this->solicitudConDocumentos();

        Storage::disk('private')->assertExists($solicitud->documento_path);

        app(VerificacionArrendadorService::class)
            ->aprobar($solicitud, User::factory()->admin()->create());

        Storage::disk('private')->assertMissing($solicitud->documento_path);
        Storage::disk('private')->assertMissing($solicitud->prueba_path);
    }

    public function test_al_rechazar_tambien(): void
    {
        // Si vuelve a intentarlo subirá documentos nuevos; no hay motivo
        // para conservar los de un intento fallido.
        $solicitud = $this->solicitudConDocumentos();

        app(VerificacionArrendadorService::class)->rechazar(
            $solicitud,
            User::factory()->admin()->create(),
            'La cédula que subiste está borrosa y no se lee el número.',
        );

        Storage::disk('private')->assertMissing($solicitud->documento_path);
    }

    public function test_queda_constancia_de_cuando_se_borraron(): void
    {
        // La auditoría tiene que poder decir «hubo cédula, se revisó y se
        // eliminó el día tal».
        $solicitud = $this->solicitudConDocumentos();

        $this->assertNull($solicitud->documentos_borrados_en);
        $this->assertTrue($solicitud->tieneDocumentos());

        app(VerificacionArrendadorService::class)
            ->aprobar($solicitud, User::factory()->admin()->create());

        $solicitud->refresh();
        $this->assertNotNull($solicitud->documentos_borrados_en);
        $this->assertFalse($solicitud->tieneDocumentos());
    }

    public function test_aprobar_sigue_funcionando_igual_de_lo_demas(): void
    {
        // Borrar los documentos no puede llevarse por delante la aprobación.
        $solicitud = $this->solicitudConDocumentos();

        app(VerificacionArrendadorService::class)
            ->aprobar($solicitud, User::factory()->admin()->create());

        $usuario = $solicitud->user->fresh();
        $this->assertTrue($usuario->esArrendador());
        $this->assertNotNull($usuario->verificado_en);
    }

    public function test_mientras_esta_pendiente_los_documentos_siguen_ahi(): void
    {
        // Si se borraran antes de revisarlos, no habría nada que revisar.
        $solicitud = $this->solicitudConDocumentos();

        Storage::disk('private')->assertExists($solicitud->documento_path);
        $this->assertTrue($solicitud->tieneDocumentos());
    }

    public function test_el_admin_ve_que_ya_se_eliminaron_en_vez_de_un_enlace_roto(): void
    {
        $solicitud = $this->solicitudConDocumentos();
        $admin     = User::factory()->admin()->create();

        app(VerificacionArrendadorService::class)->aprobar($solicitud, $admin);

        $this->actingAs($admin)
            ->get(route('admin.solicitudes.show', $solicitud))
            ->assertOk()
            ->assertSee('Ya se eliminaron')
            ->assertDontSee('Ver documento de identidad');
    }

    public function test_el_comando_limpia_lo_que_quedo_de_antes(): void
    {
        // Las solicitudes resueltas antes de este cambio conservan los
        // archivos: nadie las va a volver a tocar y el comando las alcanza.
        $solicitud = $this->solicitudConDocumentos();
        $solicitud->forceFill([
            'estado'      => SolicitudArrendador::APROBADA,
            'revisada_en' => now()->subMonths(6),
        ])->save();

        Storage::disk('private')->assertExists($solicitud->documento_path);

        Artisan::call('habbi:limpiar-documentos');

        Storage::disk('private')->assertMissing($solicitud->documento_path);
        $this->assertNotNull($solicitud->fresh()->documentos_borrados_en);
    }

    public function test_el_comando_no_toca_las_solicitudes_sin_revisar(): void
    {
        $solicitud = $this->solicitudConDocumentos();

        Artisan::call('habbi:limpiar-documentos');

        Storage::disk('private')->assertExists($solicitud->documento_path);
    }

    public function test_listar_no_borra_nada(): void
    {
        $solicitud = $this->solicitudConDocumentos();
        $solicitud->forceFill(['estado' => SolicitudArrendador::APROBADA])->save();

        Artisan::call('habbi:limpiar-documentos', ['--listar' => true]);

        Storage::disk('private')->assertExists($solicitud->documento_path);
    }

    public function test_borrar_dos_veces_no_falla(): void
    {
        $solicitud = $this->solicitudConDocumentos();
        $servicio  = app(VerificacionArrendadorService::class);

        $servicio->aprobar($solicitud, User::factory()->admin()->create());
        $primeraFecha = $solicitud->fresh()->documentos_borrados_en;

        $servicio->eliminarDocumentos($solicitud->fresh());

        // La fecha no se pisa: la que importa es cuándo desaparecieron.
        $this->assertEquals($primeraFecha, $solicitud->fresh()->documentos_borrados_en);
    }
}
