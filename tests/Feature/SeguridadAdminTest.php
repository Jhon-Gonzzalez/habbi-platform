<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Texto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeguridadAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_contrasenas_se_guardan_cifradas_y_no_se_pueden_leer(): void
    {
        $usuario = User::factory()->create(['password' => 'miClaveSecreta123']);

        $guardada = $usuario->fresh()->password;

        $this->assertNotSame('miClaveSecreta123', $guardada);
        $this->assertStringStartsWith('$2y$', $guardada);       // bcrypt
        $this->assertTrue(Hash::check('miClaveSecreta123', $guardada));
    }

    public function test_un_administrador_no_puede_cambiar_la_contrasena_de_otro(): void
    {
        $admin   = User::factory()->admin()->create();
        $victima = User::factory()->create(['password' => 'suClaveOriginal1']);
        $antes   = $victima->fresh()->password;

        $this->actingAs($admin)->put(route('admin.usuarios.update', $victima), [
            'name'                  => $victima->name,
            'email'                 => $victima->email,
            'role'                  => 'user',
            'password'              => 'claveIntrusa999',
            'password_confirmation' => 'claveIntrusa999',
        ])->assertRedirect(route('admin.usuarios.index'));

        $despues = $victima->fresh()->password;

        // El resto de datos sí se actualiza, pero la contraseña queda intacta.
        $this->assertSame($antes, $despues, 'La contraseña fue modificada por un administrador.');
        $this->assertTrue(Hash::check('suClaveOriginal1', $despues));
        $this->assertFalse(Hash::check('claveIntrusa999', $despues));
    }

    public function test_el_formulario_de_edicion_no_ofrece_campo_de_contrasena(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.usuarios.edit', $usuario))
            ->assertOk()
            ->assertDontSee('name="password"', false)
            ->assertSee('La contraseña no se puede cambiar desde aquí');
    }

    public function test_el_administrador_puede_generar_un_enlace_de_restablecimiento(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.usuarios.enlace-reset', $usuario))
            ->assertRedirect()
            ->assertSessionHas('enlace_reset');

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $usuario->email]);
    }

    public function test_un_usuario_normal_no_puede_generar_enlaces_de_restablecimiento(): void
    {
        $intruso = User::factory()->create();
        $otro    = User::factory()->create();

        $this->actingAs($intruso)
            ->post(route('admin.usuarios.enlace-reset', $otro))
            ->assertForbidden();
    }

    public function test_el_listado_oculta_parcialmente_los_correos(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create(['email' => 'ana.martinez@gmail.com']);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertDontSee('ana.martinez@gmail.com')
            ->assertSee('an••••••••••@gmail.com');
    }

    public function test_la_ficha_del_usuario_si_muestra_el_correo_completo(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create(['email' => 'ana.martinez@gmail.com']);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.show', $usuario))
            ->assertOk()
            ->assertSee('ana.martinez@gmail.com');
    }

    public function test_ocultar_correo_maneja_casos_raros(): void
    {
        $this->assertSame('—', Texto::ocultarCorreo(null));
        $this->assertSame('—', Texto::ocultarCorreo('sin-arroba'));
        $this->assertSame('a•••@b.co', Texto::ocultarCorreo('a@b.co'));
    }
}
