<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_de_inicio_carga(): void
    {
        $this->get(route('index'))->assertOk()->assertSee('HABBI', false);
    }

    public function test_el_formulario_de_registro_carga(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Crear cuenta');
    }

    public function test_un_usuario_puede_registrarse_y_queda_con_rol_user(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Ana Martinez',
            'email'                 => 'ana@habbi.test',
            'password'              => 'clave1234',
            'password_confirmation' => 'clave1234',
        ])->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', ['email' => 'ana@habbi.test', 'role' => 'user']);
        $this->assertAuthenticated();
    }

    public function test_el_registro_rechaza_contrasenas_sin_numeros(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Ana Martinez',
            'email'                 => 'ana2@habbi.test',
            'password'              => 'solamenteletras',
            'password_confirmation' => 'solamenteletras',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_un_usuario_puede_iniciar_y_cerrar_sesion(): void
    {
        $usuario = User::factory()->create(['password' => bcrypt('clave1234')]);

        $this->post(route('login'), ['email' => $usuario->email, 'password' => 'clave1234'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($usuario);

        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_el_panel_personal_requiere_sesion(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Mi cuenta', false);
    }
}
