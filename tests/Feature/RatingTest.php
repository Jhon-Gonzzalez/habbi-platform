<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_invitado_no_puede_calificar(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->post(route('ratings.store', $alojamiento), ['rating' => 5])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_un_usuario_puede_calificar_un_alojamiento_ajeno(): void
    {
        $alojamiento = Alojamiento::factory()->create();
        $estudiante  = User::factory()->create();

        $this->actingAs($estudiante)
            ->post(route('ratings.store', $alojamiento), [
                'rating'  => 4,
                'comment' => 'Muy buena ubicacion y el arrendador responde rapido.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ratings', [
            'user_id'        => $estudiante->id,
            'alojamiento_id' => $alojamiento->id,
            'rating'         => 4,
        ]);
    }

    public function test_nadie_puede_calificar_su_propio_alojamiento(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs($alojamiento->user)
            ->post(route('ratings.store', $alojamiento), ['rating' => 5])
            ->assertForbidden();

        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_ni_un_administrador_puede_calificar_su_propio_alojamiento(): void
    {
        $admin       = User::factory()->admin()->create();
        $alojamiento = Alojamiento::factory()->for($admin)->create();

        $this->actingAs($admin)
            ->post(route('ratings.store', $alojamiento), ['rating' => 5])
            ->assertForbidden();
    }

    public function test_calificar_dos_veces_actualiza_la_resena_en_lugar_de_duplicarla(): void
    {
        $alojamiento = Alojamiento::factory()->create();
        $estudiante  = User::factory()->create();

        $this->actingAs($estudiante)->post(route('ratings.store', $alojamiento), ['rating' => 2]);
        $this->actingAs($estudiante)->post(route('ratings.store', $alojamiento), ['rating' => 5]);

        $this->assertDatabaseCount('ratings', 1);
        $this->assertDatabaseHas('ratings', [
            'user_id'        => $estudiante->id,
            'alojamiento_id' => $alojamiento->id,
            'rating'         => 5,
        ]);
    }

    public function test_la_calificacion_debe_estar_entre_uno_y_cinco(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('ratings.store', $alojamiento), ['rating' => 9])
            ->assertSessionHasErrors('rating');
    }

    public function test_un_usuario_solo_puede_borrar_su_propia_resena(): void
    {
        $resena  = Rating::factory()->create();
        $intruso = User::factory()->create();

        $this->actingAs($intruso)
            ->delete(route('ratings.destroy', $resena))
            ->assertForbidden();

        $this->actingAs($resena->user)
            ->delete(route('ratings.destroy', $resena))
            ->assertRedirect();

        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_el_promedio_se_calcula_correctamente(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        foreach ([5, 4, 3] as $puntuacion) {
            Rating::factory()->create([
                'alojamiento_id' => $alojamiento->id,
                'rating'         => $puntuacion,
            ]);
        }

        $this->assertSame(4.0, $alojamiento->fresh()->promedio());
        $this->assertSame(3, $alojamiento->fresh()->totalResenas());
    }
}
