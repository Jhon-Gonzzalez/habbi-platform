<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DisponibilidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_fecha_se_considera_disponible_ya(): void
    {
        // Es el caso más común y no tiene sentido obligar a escribir hoy.
        $a = Alojamiento::factory()->create(['disponible_desde' => null]);

        $this->assertTrue($a->estaDisponibleYa());
        $this->assertSame('Disponible ya', $a->textoDisponibilidad());
    }

    public function test_una_fecha_pasada_tambien_cuenta_como_ya(): void
    {
        // El arrendador puso enero, llegó marzo y nadie volvió a editarlo.
        // Mostrar «disponible desde enero» en marzo confunde más que ayuda.
        $a = Alojamiento::factory()->create(['disponible_desde' => now()->subMonths(3)]);

        $this->assertTrue($a->estaDisponibleYa());
        $this->assertSame('Disponible ya', $a->textoDisponibilidad());
    }

    public function test_hoy_cuenta_como_disponible_ya(): void
    {
        $a = Alojamiento::factory()->create(['disponible_desde' => now()]);

        $this->assertTrue($a->estaDisponibleYa());
    }

    public function test_una_fecha_futura_se_anuncia_en_castellano(): void
    {
        $a = Alojamiento::factory()->create(['disponible_desde' => '2027-06-15']);

        $this->assertFalse($a->estaDisponibleYa());
        $this->assertSame('Disponible desde el 15 de junio', $a->textoDisponibilidad());
    }

    public function test_el_aviso_sale_en_la_tarjeta_solo_si_no_esta_libre(): void
    {
        Alojamiento::factory()->create([
            'title'            => 'Cuarto que se desocupa luego',
            'disponible_desde' => now()->addMonths(4),
        ]);
        Alojamiento::factory()->create([
            'title'            => 'Cuarto libre ahora mismo',
            'disponible_desde' => null,
        ]);

        $html = $this->get(route('alojamientos.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Disponible desde el', $html);
        // El que está libre no lleva etiqueta: sería ruido en todas las tarjetas.
        $this->assertSame(1, substr_count($html, 'Disponible desde el'));
    }

    public function test_al_publicar_se_guarda_la_fecha(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->arrendador()->create())
            ->post(route('alojamientos.store'), [
                'title'            => 'Habitación que se desocupa en junio',
                'type'             => 'Habitación',
                'price'            => 450000,
                'price_period'     => 'mes',
                'guests'           => 1,
                'city'             => 'Montería',
                'description'      => 'Habitación amplia con baño privado y wifi incluido.',
                'phone'            => '3001234567',
                'disponible_desde' => '2027-06-01',
                'photos'           => [UploadedFile::fake()->image('cuarto.jpg')],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('2027-06-01', Alojamiento::latest('id')->first()->disponible_desde->format('Y-m-d'));
    }

    public function test_se_puede_editar_un_anuncio_viejo_con_la_fecha_ya_pasada(): void
    {
        // Si se exigiera que la fecha fuese futura, cambiar el precio de un
        // anuncio de hace meses obligaría a borrar primero la fecha.
        $arrendador  = User::factory()->arrendador()->create();
        $alojamiento = Alojamiento::factory()->for($arrendador)->create([
            'disponible_desde' => now()->subYear(),
            'price'            => 400000,
        ]);

        $this->actingAs($arrendador)
            ->put(route('alojamientos.update', $alojamiento), [
                'title'            => $alojamiento->title,
                'type'             => $alojamiento->type,
                'price'            => 500000,
                'price_period'     => 'mes',
                'guests'           => 1,
                'city'             => $alojamiento->city,
                'description'      => $alojamiento->description,
                'phone'            => '3001234567',
                'disponible_desde' => now()->subYear()->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(500000, $alojamiento->fresh()->price);
    }

    public function test_rechaza_una_fecha_que_no_es_una_fecha(): void
    {
        $this->actingAs(User::factory()->arrendador()->create())
            ->post(route('alojamientos.store'), [
                'title'            => 'Cualquiera',
                'type'             => 'Habitación',
                'price'            => 450000,
                'price_period'     => 'mes',
                'guests'           => 1,
                'city'             => 'Montería',
                'description'      => 'Una descripción suficientemente larga para pasar.',
                'phone'            => '3001234567',
                'disponible_desde' => 'cuando se vaya el otro',
            ])
            ->assertSessionHasErrors('disponible_desde');
    }

    public function test_el_filtro_encuentra_los_que_se_pueden_ocupar_a_tiempo(): void
    {
        $ya      = Alojamiento::factory()->create(['disponible_desde' => null]);
        $enMarzo = Alojamiento::factory()->create(['disponible_desde' => '2027-03-01']);
        $enJulio = Alojamiento::factory()->create(['disponible_desde' => '2027-07-01']);

        $ids = Alojamiento::disponibleAntesDe('2027-04-01')->pluck('id');

        $this->assertContains($ya->id, $ids);
        $this->assertContains($enMarzo->id, $ids);
        $this->assertNotContains($enJulio->id, $ids);
    }
}
