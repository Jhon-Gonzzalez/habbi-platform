<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoritoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_invitado_no_puede_guardar_favoritos(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->post(route('favoritos.toggle', $alojamiento))->assertRedirect(route('login'));
        $this->get(route('favoritos.index'))->assertRedirect(route('login'));
    }

    public function test_guardar_y_quitar_con_el_mismo_boton(): void
    {
        $user        = User::factory()->create();
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs($user)->post(route('favoritos.toggle', $alojamiento));
        $this->assertTrue($user->fresh()->tieneEnFavoritos($alojamiento));

        $this->actingAs($user)->post(route('favoritos.toggle', $alojamiento));
        $this->assertFalse($user->fresh()->tieneEnFavoritos($alojamiento));
    }

    public function test_no_se_duplica_al_guardar_dos_veces_seguidas(): void
    {
        $user        = User::factory()->create();
        $alojamiento = Alojamiento::factory()->create();

        // Doble clic o doble envío del formulario.
        $user->favoritos()->syncWithoutDetaching([$alojamiento->id]);
        $user->favoritos()->syncWithoutDetaching([$alojamiento->id]);

        $this->assertSame(1, $user->favoritos()->count());
    }

    public function test_el_listado_muestra_solo_mis_favoritos(): void
    {
        $yo   = User::factory()->create();
        $otro = User::factory()->create();

        $mio   = Alojamiento::factory()->create(['title' => 'Cuarto que yo guardé']);
        $suyo  = Alojamiento::factory()->create(['title' => 'Cuarto que guardó el otro']);

        $yo->favoritos()->attach($mio);
        $otro->favoritos()->attach($suyo);

        $this->actingAs($yo)
            ->get(route('favoritos.index'))
            ->assertOk()
            ->assertSee('Cuarto que yo guardé')
            ->assertDontSee('Cuarto que guardó el otro');
    }

    public function test_un_favorito_pausado_no_aparece_en_el_listado(): void
    {
        // Si el arrendador pausa el anuncio, deja de ser una opción real.
        $user        = User::factory()->create();
        $alojamiento = Alojamiento::factory()->pausado()->create(['title' => 'Anuncio pausado']);

        $user->favoritos()->attach($alojamiento);

        $this->actingAs($user)
            ->get(route('favoritos.index'))
            ->assertOk()
            ->assertDontSee('Anuncio pausado');
    }

    public function test_el_listado_vacio_explica_que_hacer(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('favoritos.index'))
            ->assertOk()
            ->assertSee('Todavía no has guardado nada');
    }

    public function test_al_borrar_el_alojamiento_desaparece_el_favorito(): void
    {
        $user        = User::factory()->create();
        $alojamiento = Alojamiento::factory()->create();
        $user->favoritos()->attach($alojamiento);

        $alojamiento->delete();

        $this->assertSame(0, $user->favoritos()->count());
    }

    public function test_el_corazon_se_muestra_relleno_cuando_ya_esta_guardado(): void
    {
        $user        = User::factory()->create();
        $alojamiento = Alojamiento::factory()->create();
        $user->favoritos()->attach($alojamiento);

        $this->actingAs($user)
            ->get(route('alojamientos.show', $alojamiento))
            ->assertOk()
            ->assertSee('Guardado en favoritos');
    }
}
