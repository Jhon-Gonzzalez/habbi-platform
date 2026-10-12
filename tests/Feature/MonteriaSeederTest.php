<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Rating;
use App\Models\User;
use Database\Seeders\MonteriaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonteriaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_veinte_alojamientos_en_monteria(): void
    {
        $this->seed(MonteriaSeeder::class);

        $this->assertSame(20, Alojamiento::count());
        $this->assertSame(20, Alojamiento::where('city', 'Montería')->count());
    }

    public function test_cada_alojamiento_apunta_a_una_universidad_de_la_lista(): void
    {
        $this->seed(MonteriaSeeder::class);

        $validas = array_column(Alojamiento::UNIVERSIDADES, 'nombre');

        foreach (Alojamiento::all() as $alojamiento) {
            $this->assertContains(
                $alojamiento->universidad_referencia,
                $validas,
                "«{$alojamiento->title}» apunta a una universidad que no existe en la lista."
            );
        }
    }

    public function test_todos_los_alojamientos_tienen_direccion_para_la_ruta(): void
    {
        $this->seed(MonteriaSeeder::class);

        foreach (Alojamiento::all() as $alojamiento) {
            $this->assertNotEmpty($alojamiento->address);
            $this->assertStringContainsString('maps/dir/', $alojamiento->urlRuta());
        }
    }

    public function test_los_telefonos_usan_el_prefijo_reservado_para_pruebas(): void
    {
        // Si alguien cambia un teléfono por uno real, las llamadas de prueba
        // llegarían a una persona de verdad. Este test lo impide.
        $this->seed(MonteriaSeeder::class);

        foreach (Alojamiento::all() as $alojamiento) {
            $this->assertStringContainsString('300 555', $alojamiento->phone);
        }
    }

    public function test_los_correos_de_ejemplo_no_son_de_dominios_reales(): void
    {
        $this->seed(MonteriaSeeder::class);

        foreach (User::where('email', '!=', 'admin@habbi.test')->get() as $usuario) {
            $this->assertStringEndsWith('.test', $usuario->email);
        }
    }

    public function test_hay_arrendadores_verificados_y_sin_verificar(): void
    {
        $this->seed(MonteriaSeeder::class);

        $arrendadores = User::where('role', User::ROLE_ARRENDADOR)->get();

        $this->assertTrue($arrendadores->whereNotNull('verificado_en')->isNotEmpty());
        $this->assertTrue($arrendadores->whereNull('verificado_en')->isNotEmpty());
    }

    public function test_las_resenas_las_escriben_estudiantes_distintos(): void
    {
        $this->seed(MonteriaSeeder::class);

        $this->assertGreaterThan(40, Rating::count());

        // Nadie reseña dos veces el mismo alojamiento.
        $duplicados = Rating::selectRaw('user_id, alojamiento_id, count(*) as total')
            ->groupBy('user_id', 'alojamiento_id')
            ->havingRaw('count(*) > 1')
            ->get();

        $this->assertCount(0, $duplicados);
    }

    public function test_el_seeder_se_puede_correr_dos_veces_sin_duplicar(): void
    {
        $this->seed(MonteriaSeeder::class);
        $this->seed(MonteriaSeeder::class);

        $this->assertSame(20, Alojamiento::count());
    }

    public function test_el_listado_publico_muestra_los_alojamientos_sembrados(): void
    {
        $this->seed(MonteriaSeeder::class);

        $this->get(route('alojamientos.index'))
            ->assertOk()
            ->assertSee('Montería');
    }

    public function test_el_seeder_no_corre_en_produccion(): void
    {
        app()['env'] = 'production';

        // Se instancia directo en vez de $this->seed() porque en producción
        // Artisan pide confirmación antes de llegar al guard del seeder.
        (new MonteriaSeeder())->run();

        $this->assertSame(0, Alojamiento::count());
    }
}
