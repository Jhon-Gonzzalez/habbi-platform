<?php

namespace Tests\Feature;

use App\Models\SolicitudArrendador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificacionArrendadorTest extends TestCase
{
    use RefreshDatabase;

    /* ---------- Quién puede publicar ---------- */

    public function test_registrarse_no_da_permiso_para_publicar(): void
    {
        $estudiante = User::factory()->create();

        $this->assertFalse($estudiante->puedePublicar());

        $this->actingAs($estudiante)
            ->get(route('alojamientos.create'))
            ->assertRedirect(route('arrendador.solicitud'));
    }

    public function test_un_estudiante_no_puede_guardar_un_alojamiento_aunque_envie_el_formulario(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post(route('alojamientos.store'), [
                'title'        => 'Intento de publicar sin verificar',
                'type'         => 'Habitación',
                'price'        => 500000,
                'price_period' => 'mes',
                'guests'       => 1,
                'city'         => 'Monteria',
                'description'  => 'Descripcion con la longitud suficiente para pasar la validacion del formulario.',
                'phone'        => '+57 300 123 4567',
                'photos'       => [UploadedFile::fake()->image('foto.jpg')],
            ])
            ->assertRedirect(route('arrendador.solicitud'));

        $this->assertDatabaseCount('alojamientos', 0);
    }

    public function test_un_arrendador_verificado_si_puede_publicar(): void
    {
        $this->actingAs(User::factory()->arrendador()->create())
            ->get(route('alojamientos.create'))
            ->assertOk();
    }

    /* ---------- La solicitud ---------- */

    public function test_un_estudiante_envia_su_solicitud_con_documentos(): void
    {
        Storage::fake('private');
        $estudiante = User::factory()->create();

        $this->actingAs($estudiante)->post(route('arrendador.solicitud.enviar'), [
            'telefono'  => '+57 300 123 4567',
            'mensaje'   => 'Tengo tres habitaciones en el barrio La Granja, cerca de Unicordoba.',
            'documento' => UploadedFile::fake()->image('cedula.jpg'),
            'prueba'    => UploadedFile::fake()->image('recibo.jpg'),
            'acepta'    => '1',
        ])->assertRedirect(route('arrendador.solicitud'));

        $solicitud = SolicitudArrendador::first();

        $this->assertNotNull($solicitud);
        $this->assertSame(SolicitudArrendador::PENDIENTE, $solicitud->estado);
        $this->assertSame($estudiante->id, $solicitud->user_id);

        // Lo importante: los documentos van al disco privado, no al público.
        Storage::disk('private')->assertExists($solicitud->documento_path);
        Storage::disk('private')->assertExists($solicitud->prueba_path);
    }

    public function test_sin_autorizar_el_tratamiento_de_datos_la_solicitud_se_rechaza(): void
    {
        Storage::fake('private');

        $this->actingAs(User::factory()->create())
            ->post(route('arrendador.solicitud.enviar'), [
                'telefono'  => '+57 300 123 4567',
                'mensaje'   => 'Tengo tres habitaciones en el barrio La Granja, cerca de Unicordoba.',
                'documento' => UploadedFile::fake()->image('cedula.jpg'),
                'prueba'    => UploadedFile::fake()->image('recibo.jpg'),
                // sin 'acepta'
            ])
            ->assertSessionHasErrors('acepta');

        $this->assertDatabaseCount('solicitudes_arrendador', 0);
    }

    public function test_no_se_puede_enviar_una_segunda_solicitud_con_una_pendiente(): void
    {
        Storage::fake('private');
        $estudiante = User::factory()->create();

        SolicitudArrendador::factory()->for($estudiante)->create();

        $this->actingAs($estudiante)
            ->post(route('arrendador.solicitud.enviar'), [
                'telefono'  => '+57 300 123 4567',
                'mensaje'   => 'Tengo tres habitaciones en el barrio La Granja, cerca de Unicordoba.',
                'documento' => UploadedFile::fake()->image('cedula.jpg'),
                'prueba'    => UploadedFile::fake()->image('recibo.jpg'),
                'acepta'    => '1',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('solicitudes_arrendador', 1);
    }

    /* ---------- La revisión ---------- */

    public function test_al_aprobar_el_usuario_pasa_a_arrendador_y_queda_verificado(): void
    {
        $admin     = User::factory()->admin()->create();
        $solicitud = SolicitudArrendador::factory()->create();

        $this->assertFalse($solicitud->user->puedePublicar());

        $this->actingAs($admin)
            ->post(route('admin.solicitudes.aprobar', $solicitud))
            ->assertRedirect(route('admin.solicitudes.index'));

        $usuario = $solicitud->user->fresh();

        $this->assertTrue($usuario->esArrendador());
        $this->assertTrue($usuario->puedePublicar());
        $this->assertTrue($usuario->estaVerificado());
        $this->assertSame(SolicitudArrendador::APROBADA, $solicitud->fresh()->estado);
        $this->assertSame($admin->id, $solicitud->fresh()->revisada_por);
    }

    public function test_al_rechazar_hace_falta_un_motivo_que_el_usuario_pueda_leer(): void
    {
        $admin     = User::factory()->admin()->create();
        $solicitud = SolicitudArrendador::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.solicitudes.rechazar', $solicitud), ['motivo' => 'corto'])
            ->assertSessionHasErrors('motivo');

        $this->actingAs($admin)
            ->post(route('admin.solicitudes.rechazar', $solicitud), [
                'motivo' => 'La foto de la cedula esta borrosa y no se lee el numero.',
            ])
            ->assertRedirect(route('admin.solicitudes.index'));

        $solicitud->refresh();

        $this->assertTrue($solicitud->fueRechazada());
        $this->assertFalse($solicitud->user->fresh()->puedePublicar());
        $this->assertStringContainsString('borrosa', $solicitud->motivo_rechazo);
    }

    public function test_tras_un_rechazo_se_puede_volver_a_intentar(): void
    {
        Storage::fake('private');
        $estudiante = User::factory()->create();

        SolicitudArrendador::factory()->for($estudiante)->rechazada()->create();

        $this->actingAs($estudiante)
            ->post(route('arrendador.solicitud.enviar'), [
                'telefono'  => '+57 300 123 4567',
                'mensaje'   => 'Vuelvo a enviarla con la cedula bien enfocada esta vez, gracias.',
                'documento' => UploadedFile::fake()->image('cedula.jpg'),
                'prueba'    => UploadedFile::fake()->image('recibo.jpg'),
                'acepta'    => '1',
            ])
            ->assertRedirect(route('arrendador.solicitud'));

        $this->assertDatabaseCount('solicitudes_arrendador', 2);
    }

    public function test_un_usuario_normal_no_puede_aprobar_solicitudes(): void
    {
        $solicitud = SolicitudArrendador::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.solicitudes.aprobar', $solicitud))
            ->assertForbidden();

        $this->assertFalse($solicitud->user->fresh()->puedePublicar());
    }

    /* ---------- Los documentos ---------- */

    public function test_los_documentos_no_son_accesibles_para_un_tercero(): void
    {
        Storage::fake('private');
        $solicitud = SolicitudArrendador::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('arrendador.documento', [$solicitud, 'documento']))
            ->assertForbidden();
    }

    public function test_el_dueno_y_el_admin_si_pueden_ver_los_documentos(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('verificaciones/1/documento/cedula.jpg', 'contenido');

        $solicitud = SolicitudArrendador::factory()->create([
            'documento_path' => 'verificaciones/1/documento/cedula.jpg',
        ]);

        $this->actingAs($solicitud->user)
            ->get(route('arrendador.documento', [$solicitud, 'documento']))
            ->assertOk();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('arrendador.documento', [$solicitud, 'documento']))
            ->assertOk();
    }

    /* ---------- Revocar ---------- */

    public function test_un_admin_puede_retirar_la_condicion_de_arrendador(): void
    {
        $admin       = User::factory()->admin()->create();
        $arrendador  = User::factory()->arrendador()->create();

        $this->actingAs($admin)
            ->post(route('admin.usuarios.revocar', $arrendador))
            ->assertRedirect();

        $arrendador->refresh();

        $this->assertTrue($arrendador->esEstudiante());
        $this->assertFalse($arrendador->puedePublicar());
        $this->assertFalse($arrendador->estaVerificado());
    }
}
