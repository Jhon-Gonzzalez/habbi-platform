<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\TelefonoVisto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TelefonoProtegidoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Los límites se guardan en caché entre tests y harían fallar a los
        // que vienen después por culpa de los de antes.
        RateLimiter::clear('telefonos');
        cache()->flush();
    }

    private function alojamiento(): Alojamiento
    {
        return Alojamiento::factory()->create(['phone' => '+57 300 555 0101']);
    }

    /* ===================== Que no salga impreso ===================== */

    public function test_el_telefono_no_aparece_en_el_html_de_la_publicacion(): void
    {
        // Lo importante: si saliera impreso, bastaría una cuenta nueva y un
        // recorrido por el listado para llevarse todos los contactos.
        $alojamiento = $this->alojamiento();

        $this->actingAs(User::factory()->create())
            ->get(route('alojamientos.show', $alojamiento))
            ->assertOk()
            ->assertDontSee('300 555 0101')
            ->assertDontSee('573005550101')
            ->assertSee('Ver teléfono');
    }

    public function test_un_invitado_no_puede_pedir_el_telefono(): void
    {
        $this->get(route('alojamientos.telefono', $this->alojamiento()))
            ->assertRedirect(route('login'));
    }

    /* ===================== Pedirlo deja rastro ===================== */

    public function test_pedirlo_devuelve_el_numero_y_queda_registrado(): void
    {
        $user        = User::factory()->create();
        $alojamiento = $this->alojamiento();

        $this->actingAs($user)
            ->getJson(route('alojamientos.telefono', $alojamiento))
            ->assertOk()
            ->assertJsonPath('telefono', '+57 300 555 0101');

        $this->assertDatabaseHas('telefonos_vistos', [
            'user_id'        => $user->id,
            'alojamiento_id' => $alojamiento->id,
        ]);
    }

    public function test_sin_javascript_tambien_funciona_y_tambien_registra(): void
    {
        // La protección no puede depender de que el navegador colabore.
        $user        = User::factory()->create();
        $alojamiento = $this->alojamiento();

        $this->actingAs($user)
            ->get(route('alojamientos.telefono', $alojamiento))
            ->assertOk()
            ->assertSee('+57 300 555 0101');

        $this->assertSame(1, TelefonoVisto::count());
    }

    public function test_el_dueno_y_el_admin_no_ensucian_las_estadisticas(): void
    {
        $arrendador  = User::factory()->arrendador()->create();
        $alojamiento = Alojamiento::factory()->for($arrendador)->create(['phone' => '+57 300 555 0101']);

        $this->actingAs($arrendador)->getJson(route('alojamientos.telefono', $alojamiento))->assertOk();
        $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('alojamientos.telefono', $alojamiento))->assertOk();

        $this->assertSame(0, TelefonoVisto::count());
    }

    public function test_un_alojamiento_sin_telefono_da_404(): void
    {
        $alojamiento = Alojamiento::factory()->create(['phone' => null]);

        $this->actingAs(User::factory()->create())
            ->get(route('alojamientos.telefono', $alojamiento))
            ->assertNotFound();
    }

    /* ===================== El límite ===================== */

    public function test_cosechar_teléfonos_se_corta_al_llegar_al_limite(): void
    {
        $user = User::factory()->create();

        // 25 por hora es el límite; un estudiante de verdad mira quince.
        $alojamientos = Alojamiento::factory()->count(26)->create(['phone' => '+57 300 555 0101']);

        $cortado = false;

        foreach ($alojamientos as $a) {
            $r = $this->actingAs($user)->getJson(route('alojamientos.telefono', $a));

            if ($r->status() === 429) {
                $cortado = true;
                break;
            }
        }

        $this->assertTrue($cortado, 'Se pudieron pedir 26 teléfonos sin que nada lo frenara.');
    }

    public function test_el_limite_corta_donde_dice_que_corta(): void
    {
        // Este test existe por un error real: las reglas «por hora» y «por
        // día» compartían clave, así que cada petición descontaba dos veces
        // y el corte llegaba en la 13 en vez de la 25. Sin fijar el número,
        // un límite mal puesto pasa desapercibido: sigue cortando, solo que
        // donde no toca, y estorba a gente normal.
        $user         = User::factory()->create();
        $alojamientos = Alojamiento::factory()->count(30)->create(['phone' => '+57 300 555 0101']);

        $atendidas = 0;

        foreach ($alojamientos as $a) {
            if ($this->actingAs($user)->getJson(route('alojamientos.telefono', $a))->status() === 429) {
                break;
            }
            $atendidas++;
        }

        $this->assertSame(25, $atendidas,
            "El límite debería dejar pasar 25 y dejó pasar {$atendidas}.");
    }

    public function test_el_limite_es_por_persona_no_global(): void
    {
        // Que uno abuse no puede dejar sin servicio a los demás.
        $alojamiento = $this->alojamiento();
        $abusador    = User::factory()->create();

        for ($i = 0; $i < 26; $i++) {
            $this->actingAs($abusador)->getJson(route('alojamientos.telefono', $alojamiento));
        }

        $this->actingAs(User::factory()->create())
            ->getJson(route('alojamientos.telefono', $alojamiento))
            ->assertOk();
    }

    /* ===================== Lo ve el administrador ===================== */

    public function test_el_panel_avisa_de_quien_pide_demasiados(): void
    {
        $cosechador = User::factory()->create(['name' => 'Cuenta Sospechosa']);

        TelefonoVisto::factory()->count(22)->create(['user_id' => $cosechador->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Cuenta Sospechosa')
            ->assertSee('Alguien está pidiendo muchos teléfonos');
    }

    public function test_el_panel_no_avisa_por_un_uso_normal(): void
    {
        $normal = User::factory()->create(['name' => 'Estudiante Normal']);

        TelefonoVisto::factory()->count(8)->create(['user_id' => $normal->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee('Alguien está pidiendo muchos teléfonos');
    }
}
