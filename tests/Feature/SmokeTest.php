<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Comprueba que todas las vistas se renderizan sin errores de Blade. */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_todas_las_paginas_publicas_responden(): void
    {
        Alojamiento::factory()->count(3)->create();

        foreach ([route('index'), route('alojamientos.index'), route('login'), route('register'), route('password.request')] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_todas_las_paginas_de_usuario_responden(): void
    {
        $usuario     = User::factory()->create();
        $alojamiento = Alojamiento::factory()->for($usuario)->create();
        Rating::factory()->create(['alojamiento_id' => $alojamiento->id]);

        $rutas = [
            route('home'),
            route('alojamientos.create'),
            route('alojamientos.mine'),
            route('alojamientos.show', $alojamiento),
            route('alojamientos.edit', $alojamiento),
        ];

        foreach ($rutas as $url) {
            $this->actingAs($usuario)->get($url)->assertOk();
        }
    }

    public function test_el_detalle_muestra_el_formulario_de_resena_a_un_visitante_distinto(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('alojamientos.show', $alojamiento))
            ->assertOk()
            ->assertSee('Publicar rese', false);
    }

    public function test_todas_las_paginas_de_administracion_responden(): void
    {
        $admin       = User::factory()->admin()->create();
        $otro        = User::factory()->create();
        $alojamiento = Alojamiento::factory()->for($otro)->create();
        Rating::factory()->create(['alojamiento_id' => $alojamiento->id]);

        $rutas = [
            route('admin.index'),
            route('admin.usuarios.index'),
            route('admin.usuarios.create'),
            route('admin.usuarios.show', $otro),
            route('admin.usuarios.edit', $otro),
            route('admin.alojamientos.index'),
        ];

        foreach ($rutas as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_un_alojamiento_pausado_devuelve_404_a_terceros_pero_no_a_su_dueno(): void
    {
        $alojamiento = Alojamiento::factory()->pausado()->create();

        $this->get(route('alojamientos.show', $alojamiento))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('alojamientos.show', $alojamiento))->assertNotFound();
        $this->actingAs($alojamiento->user)->get(route('alojamientos.show', $alojamiento))->assertOk();
    }

    public function test_el_telefono_del_arrendador_no_se_muestra_a_invitados(): void
    {
        $alojamiento = Alojamiento::factory()->create(['phone' => '+57 300 999 8877']);

        $this->get(route('alojamientos.show', $alojamiento))
            ->assertOk()
            ->assertDontSee('3009998877')
            ->assertSee('Inicia sesi', false);
    }
}
