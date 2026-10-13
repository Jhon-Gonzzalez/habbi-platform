<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificacionCorreoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * La verificación viene apagada por defecto (config/habbi.php), para que
     * el proyecto funcione recién clonado sin servidor de correo. Los tests
     * que comprueban el BLOQUEO tienen que encenderla a propósito.
     */
    private function conVerificacionActiva(): void
    {
        config(['habbi.verificar_correo' => true]);
    }

    private function sinVerificar(array $extra = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => null], $extra));
    }

    /**
     * Apunta el correo a un host que no existe, para que el envío lance una
     * excepción de transporte de verdad. Mockear la fachada Mail no sirve:
     * las notificaciones no pasan por ahí, y en los tests el driver es
     * "array", que nunca falla.
     */
    private function romperElCorreo(): void
    {
        config([
            'mail.default'                => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host'      => 'host-que-no-existe.invalid',
            'mail.mailers.smtp.port'      => 1,
            'mail.mailers.smtp.timeout'   => 1,
        ]);
    }

    public function test_el_ayudante_de_pruebas_si_rompe_el_correo(): void
    {
        // Si este test deja de fallar el envío, los dos de abajo pasarían
        // en vacío sin que nos diéramos cuenta.
        $this->romperElCorreo();

        $this->expectException(\Throwable::class);

        $this->sinVerificar()->sendEmailVerificationNotification();
    }

    /* ===================== Lo que sí se puede hacer ===================== */

    public function test_sin_verificar_se_puede_buscar_y_ver_el_detalle(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs($this->sinVerificar())
            ->get(route('alojamientos.index'))->assertOk();

        $this->actingAs($this->sinVerificar())
            ->get(route('alojamientos.show', $alojamiento))->assertOk();
    }

    public function test_sin_verificar_se_pueden_guardar_favoritos(): void
    {
        // Castigar la navegación por no haber abierto un correo no protege
        // a nadie, así que favoritos no exige verificación.
        $user        = $this->sinVerificar();
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs($user)->post(route('favoritos.toggle', $alojamiento));

        $this->assertTrue($user->fresh()->tieneEnFavoritos($alojamiento));
    }

    public function test_sin_verificar_se_puede_editar_el_perfil(): void
    {
        $this->actingAs($this->sinVerificar())
            ->get(route('perfil.edit'))->assertOk();
    }

    /* ===================== Lo que queda bloqueado ===================== */

    public function test_sin_verificar_no_se_puede_resenar(): void
    {
        $this->conVerificacionActiva();

        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs($this->sinVerificar())
            ->post(route('ratings.store', $alojamiento), ['rating' => 5, 'comment' => 'Muy bueno'])
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_sin_verificar_no_se_puede_reportar(): void
    {
        $this->conVerificacionActiva();

        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs($this->sinVerificar())
            ->get(route('reportes.create', $alojamiento))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_sin_verificar_no_se_puede_pedir_ser_arrendador(): void
    {
        $this->conVerificacionActiva();

        $this->actingAs($this->sinVerificar())
            ->get(route('arrendador.solicitud'))
            ->assertRedirect(route('verification.notice'));
    }

    /* ===================== Avisos ===================== */

    public function test_el_aviso_aparece_mientras_falte_verificar(): void
    {
        $this->conVerificacionActiva();

        $this->actingAs($this->sinVerificar())
            ->get(route('alojamientos.index'))
            ->assertOk()
            ->assertSee('Falta verificar tu correo');
    }

    public function test_el_aviso_desaparece_al_verificar(): void
    {
        $this->conVerificacionActiva();

        $this->actingAs(User::factory()->create())
            ->get(route('alojamientos.index'))
            ->assertOk()
            ->assertDontSee('Falta verificar tu correo');
    }

    public function test_la_pantalla_de_verificacion_explica_que_se_desbloquea(): void
    {
        $this->actingAs($this->sinVerificar())
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Verifica tu correo');
    }

    /* ===================== Salida de emergencia del admin ===================== */

    public function test_el_admin_puede_verificar_un_correo_a_mano(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = $this->sinVerificar();

        $this->actingAs($admin)
            ->post(route('admin.usuarios.verificar-correo', $usuario))
            ->assertSessionHasNoErrors();

        $this->assertTrue($usuario->fresh()->hasVerifiedEmail());
    }

    public function test_un_usuario_normal_no_puede_verificar_correos_de_otros(): void
    {
        $otro = $this->sinVerificar();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.usuarios.verificar-correo', $otro))
            ->assertForbidden();

        $this->assertFalse($otro->fresh()->hasVerifiedEmail());
    }

    /* ===================== Cuando el correo no sale ===================== */

    public function test_el_registro_se_completa_aunque_el_correo_falle(): void
    {
        // Con la verificación apagada no se envía nada y el test no probaría
        // nada: hay que encenderla para que el listener intente enviar.
        $this->conVerificacionActiva();

        // Con el listener de serie, un SMTP caído devuelve un 500 justo
        // DESPUÉS de crear la cuenta: la persona ve un error aunque ya está
        // registrada. Esto comprueba que no pasa.
        $this->romperElCorreo();

        $respuesta = $this->post(route('register'), [
            'name'                  => 'Jhon González',
            'email'                 => 'jhon.nuevo@ejemplo.test',
            'password'              => 'ClaveSegura123',
            'password_confirmation' => 'ClaveSegura123',
        ]);

        // La cuenta se crea con cualquiera de los dos listeners; lo que
        // distingue al nuestro es que la persona NO ve una pantalla de error.
        $respuesta->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'jhon.nuevo@ejemplo.test']);
        $this->assertAuthenticated();
    }

    public function test_el_reenvio_avisa_cuando_el_correo_no_sale(): void
    {
        $usuario = $this->sinVerificar();

        $this->romperElCorreo();

        $this->actingAs($usuario)
            ->from(route('verification.notice'))
            ->post(route('verification.resend'))
            ->assertSessionHas('error');
    }

    /* ========== Con la verificación APAGADA (lo de por defecto) ========== */

    public function test_apagada_quien_se_registra_queda_verificado_solo(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Jhon González',
            'email'                 => 'jhon.nuevo@ejemplo.test',
            'password'              => 'ClaveSegura123',
            'password_confirmation' => 'ClaveSegura123',
        ]);

        $this->assertTrue(
            User::where('email', 'jhon.nuevo@ejemplo.test')->sole()->hasVerifiedEmail()
        );
    }

    public function test_apagada_no_se_envia_ningun_correo_al_registrarse(): void
    {
        // Si intentara enviar, esto lo haría estallar.
        $this->romperElCorreo();

        $this->post(route('register'), [
            'name'                  => 'Ana Pérez',
            'email'                 => 'ana@ejemplo.test',
            'password'              => 'ClaveSegura123',
            'password_confirmation' => 'ClaveSegura123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'ana@ejemplo.test']);
    }

    public function test_apagada_no_se_bloquea_resenar_ni_reportar(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs($this->sinVerificar())
            ->get(route('reportes.create', $alojamiento))
            ->assertOk();
    }

    public function test_apagada_no_aparece_el_aviso_amarillo(): void
    {
        $this->actingAs($this->sinVerificar())
            ->get(route('alojamientos.index'))
            ->assertOk()
            ->assertDontSee('Falta verificar tu correo');
    }

    public function test_el_interruptor_no_borra_nada_y_se_puede_volver_a_encender(): void
    {
        // Lo que importa de este diseño: apagar es reversible con una línea
        // del .env, sin tocar rutas ni recuperar código borrado.
        $usuario     = $this->sinVerificar();
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs($usuario)->get(route('reportes.create', $alojamiento))->assertOk();

        $this->conVerificacionActiva();

        $this->actingAs($usuario)
            ->get(route('reportes.create', $alojamiento))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verificar_a_mano_no_cambia_el_rol(): void
    {
        $usuario = $this->sinVerificar(['role' => User::ROLE_ESTUDIANTE]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.usuarios.verificar-correo', $usuario));

        $usuario->refresh();
        $this->assertSame(User::ROLE_ESTUDIANTE, $usuario->role);
        $this->assertFalse($usuario->puedePublicar());
    }
}
