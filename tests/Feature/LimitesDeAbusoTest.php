<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los límites de velocidad de las acciones que se pueden abusar.
 *
 * Los números están muy por encima de lo que hace una persona real: la
 * idea no es estorbar, es que un script no pueda llenar la plataforma de
 * reseñas falsas ni crear cuentas en masa.
 */
class LimitesDeAbusoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->flush();
    }

    public function test_no_se_pueden_crear_cuentas_en_masa(): void
    {
        $cortado = false;

        for ($i = 0; $i < 7; $i++) {
            $r = $this->post(route('register'), [
                'name'                  => "Cuenta {$i}",
                'email'                 => "cuenta{$i}@ejemplo.test",
                'password'              => 'ClaveSegura123',
                'password_confirmation' => 'ClaveSegura123',
            ]);

            if ($r->status() === 429) {
                $cortado = true;
                break;
            }

            // El registro inicia sesión; hay que salir para poder repetir.
            $this->post(route('logout'));
        }

        $this->assertTrue($cortado, 'Se crearon 7 cuentas seguidas sin que nada lo frenara.');
        $this->assertLessThan(7, User::count());
    }

    public function test_no_se_pueden_escribir_resenas_en_rafaga(): void
    {
        $user    = User::factory()->create();
        $cortado = false;

        foreach (Alojamiento::factory()->count(12)->create() as $a) {
            $r = $this->actingAs($user)->post(route('ratings.store', $a), [
                'rating'  => 5,
                'comment' => 'Excelente lugar, muy recomendado para estudiantes.',
            ]);

            if ($r->status() === 429) {
                $cortado = true;
                break;
            }
        }

        $this->assertTrue($cortado, 'Se escribieron 12 reseñas seguidas sin freno.');
    }

    public function test_no_se_puede_hostigar_a_alguien_a_base_de_reportes(): void
    {
        $user    = User::factory()->create();
        $cortado = false;

        foreach (Alojamiento::factory()->count(7)->create() as $a) {
            $r = $this->actingAs($user)->post(route('reportes.store', $a), ['motivo' => 'precio']);

            if ($r->status() === 429) {
                $cortado = true;
                break;
            }
        }

        $this->assertTrue($cortado, 'Se reportaron 7 publicaciones seguidas sin freno.');
    }

    public function test_un_uso_normal_no_se_ve_afectado(): void
    {
        // Lo que de verdad importa: que el límite no estorbe a nadie real.
        // Tres reseñas en una tarde es un estudiante normal.
        $user = User::factory()->create();

        foreach (Alojamiento::factory()->count(3)->create() as $a) {
            $this->actingAs($user)
                ->post(route('ratings.store', $a), [
                    'rating'  => 4,
                    'comment' => 'Buen sitio, la ubicación es lo mejor que tiene.',
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('ratings', 3);
    }

    public function test_el_limite_de_resenas_es_por_persona(): void
    {
        $alojamientos = Alojamiento::factory()->count(12)->create();
        $abusador     = User::factory()->create();

        foreach ($alojamientos as $a) {
            $this->actingAs($abusador)->post(route('ratings.store', $a), ['rating' => 1]);
        }

        // Otra persona, misma hora: no debe pagar por el abuso del primero.
        $this->actingAs(User::factory()->create())
            ->post(route('ratings.store', $alojamientos->first()), [
                'rating'  => 5,
                'comment' => 'A mí me pareció un sitio estupendo, sinceramente.',
            ])
            ->assertSessionHasNoErrors();
    }
}
