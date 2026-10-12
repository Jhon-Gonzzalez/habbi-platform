<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_invitado_no_puede_editar_un_perfil(): void
    {
        $this->get(route('perfil.edit'))->assertRedirect(route('login'));
    }

    public function test_el_usuario_edita_su_propio_perfil(): void
    {
        $user = User::factory()->create(['name' => 'Jhon']);

        $this->actingAs($user)
            ->put(route('perfil.update'), [
                'name'        => 'Jhon González',
                'phone'       => '3001234567',
                'bio'         => 'Estudiante de ingeniería de sistemas.',
                'ciudad'      => 'Montería',
                'universidad' => 'Universidad de Córdoba',
            ])
            ->assertRedirect(route('perfil.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Jhon González', $user->fresh()->name);
        $this->assertSame('Montería', $user->fresh()->ciudad);
    }

    public function test_el_usuario_no_puede_cambiarse_el_rol_desde_el_perfil(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ESTUDIANTE]);

        $this->actingAs($user)->put(route('perfil.update'), [
            'name' => $user->name,
            // Intento de escalar privilegios metiendo campos extra.
            'role'          => User::ROLE_ADMIN,
            'verificado_en' => now()->toDateTimeString(),
        ]);

        $user->refresh();
        $this->assertSame(User::ROLE_ESTUDIANTE, $user->role);
        $this->assertNull($user->verificado_en);
    }

    public function test_el_usuario_sube_su_foto_de_perfil(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('perfil.update'), [
            'name' => $user->name,
            'foto' => UploadedFile::fake()->image('yo.jpg', 400, 400),
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->foto_perfil);
        Storage::disk('public')->assertExists($user->foto_perfil);
    }

    public function test_el_perfil_publico_del_arrendador_es_visible_sin_sesion(): void
    {
        $arrendador = User::factory()->arrendador()->create([
            'name' => 'Marta Pérez',
            'bio'  => 'Arriendo habitaciones cerca de Unicórdoba.',
        ]);

        $this->get(route('arrendadores.perfil', $arrendador))
            ->assertOk()
            ->assertSee('Marta Pérez')
            ->assertSee('Arriendo habitaciones cerca de Unicórdoba.');
    }

    public function test_el_perfil_publico_no_muestra_el_correo_completo(): void
    {
        $arrendador = User::factory()->arrendador()->create([
            'email' => 'marta.perez@example.com',
        ]);

        $this->get(route('arrendadores.perfil', $arrendador))
            ->assertOk()
            ->assertDontSee('marta.perez@example.com');
    }

    public function test_no_existe_perfil_publico_para_un_estudiante(): void
    {
        $estudiante = User::factory()->create(['role' => User::ROLE_ESTUDIANTE]);

        $this->get(route('arrendadores.perfil', $estudiante))->assertNotFound();
    }
}
