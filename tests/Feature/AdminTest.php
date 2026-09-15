<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('admin.index'))->assertRedirect(route('login'));
    }

    public function test_un_usuario_normal_no_puede_entrar_al_panel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.index'))
            ->assertForbidden();
    }

    public function test_un_administrador_entra_al_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Resumen');
    }

    public function test_un_usuario_normal_no_puede_listar_usuarios(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.usuarios.index'))
            ->assertForbidden();
    }

    public function test_un_administrador_puede_crear_un_usuario(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.usuarios.store'), [
                'name'                  => 'Nueva Usuaria',
                'email'                 => 'nueva@habbi.test',
                'role'                  => 'user',
                'password'              => 'clave1234',
                'password_confirmation' => 'clave1234',
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertDatabaseHas('users', ['email' => 'nueva@habbi.test', 'role' => 'user']);
    }

    public function test_una_contrasena_debil_es_rechazada(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.usuarios.store'), [
                'name'                  => 'Test',
                'email'                 => 'debil@habbi.test',
                'role'                  => 'user',
                'password'              => '123',
                'password_confirmation' => '123',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_no_se_puede_eliminar_al_ultimo_administrador(): void
    {
        $admin = User::factory()->admin()->create();
        $otro  = User::factory()->admin()->create();

        // Con dos administradores sí se puede eliminar a uno.
        $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $otro));
        $this->assertModelMissing($otro);

        // Pero el último no puede eliminarse ni a sí mismo.
        $this->actingAs($admin)
            ->delete(route('admin.usuarios.destroy', $admin))
            ->assertSessionHas('error');

        $this->assertModelExists($admin);
    }

    public function test_un_administrador_puede_pausar_cualquier_alojamiento(): void
    {
        $alojamiento = Alojamiento::factory()->create(['is_active' => true]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.alojamientos.toggle', $alojamiento))
            ->assertRedirect();

        $this->assertFalse($alojamiento->fresh()->is_active);
    }

    public function test_un_administrador_puede_editar_el_alojamiento_de_otro(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('alojamientos.edit', $alojamiento))
            ->assertOk();
    }
}
