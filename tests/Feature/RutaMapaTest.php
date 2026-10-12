<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RutaMapaTest extends TestCase
{
    use RefreshDatabase;

    private function alojamiento(array $extra = []): Alojamiento
    {
        return Alojamiento::factory()->for(User::factory()->arrendador())->create(array_merge([
            'city'         => 'Montería',
            'neighborhood' => 'La Granja',
            'address'      => 'Calle 29 #6-45',
        ], $extra));
    }

    public function test_la_universidad_por_defecto_es_unicordoba(): void
    {
        $a = $this->alojamiento(['universidad_referencia' => null]);

        $this->assertSame('Universidad de Córdoba', $a->universidad()['nombre']);
    }

    public function test_usa_la_universidad_guardada_cuando_existe(): void
    {
        $a = $this->alojamiento(['universidad_referencia' => 'Universidad del Sinú']);

        $this->assertSame('Unisinú', $a->universidad()['corto']);
    }

    public function test_una_universidad_desconocida_cae_en_la_por_defecto(): void
    {
        // Si alguien edita la base a mano, el enlace no debe romperse.
        $a = $this->alojamiento(['universidad_referencia' => 'Universidad Inventada']);

        $this->assertSame('Universidad de Córdoba', $a->universidad()['nombre']);
    }

    public function test_la_ruta_apunta_a_google_maps_con_origen_y_destino(): void
    {
        $url = $this->alojamiento()->urlRuta();

        $this->assertStringStartsWith('https://www.google.com/maps/dir/?api=1', $url);
        $this->assertStringContainsString('origin=' . rawurlencode('Calle 29 #6-45, La Granja, Montería, Colombia'), $url);
        $this->assertStringContainsString('destination=' . rawurlencode('Universidad de Córdoba'), $url);
        $this->assertStringContainsString('travelmode=walking', $url);
    }

    public function test_usa_las_coordenadas_cuando_estan_disponibles(): void
    {
        $a = $this->alojamiento(['lat' => 8.7479, 'lng' => -75.8814]);

        $this->assertSame('8.7479,-75.8814', $a->direccionParaMapa());
        $this->assertStringContainsString('origin=8.7479%2C-75.8814', $a->urlRuta());
    }

    public function test_la_vista_publica_muestra_el_boton_de_la_ruta(): void
    {
        $a = $this->alojamiento();

        $this->get(route('alojamientos.show', $a))
            ->assertOk()
            ->assertSee('Ver ruta a Unicórdoba')
            ->assertSee('maps/dir/?api=1', false);
    }

    public function test_sin_direccion_no_se_muestra_el_bloque_del_mapa(): void
    {
        $a = $this->alojamiento(['address' => null, 'neighborhood' => null]);

        $this->get(route('alojamientos.show', $a))
            ->assertOk()
            ->assertDontSee('Ver ubicación');
    }

    public function test_al_publicar_se_guarda_la_universidad_elegida(): void
    {
        Storage::fake('public');
        $arrendador = User::factory()->arrendador()->create();

        $this->actingAs($arrendador)->post(route('alojamientos.store'), [
            'title'                  => 'Habitación cerca de la UPB',
            'type'                   => 'Habitación',
            'price'                  => 450000,
            'price_period'           => 'mes',
            'guests'                 => 1,
            'city'                   => 'Montería',
            'neighborhood'           => 'Los Alcázares',
            'address'                => 'Carrera 9 #44-12',
            'universidad_referencia' => 'Universidad Pontificia Bolivariana — Montería',
            'description'            => 'Habitación amplia con baño privado y wifi incluido.',
            'phone'                  => '3001234567',
            'photos'                 => [\Illuminate\Http\UploadedFile::fake()->image('cuarto.jpg')],
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            'Universidad Pontificia Bolivariana — Montería',
            Alojamiento::latest('id')->first()->universidad_referencia
        );
    }

    public function test_rechaza_una_universidad_que_no_esta_en_la_lista(): void
    {
        Storage::fake('public');
        $arrendador = User::factory()->arrendador()->create();

        $this->actingAs($arrendador)->post(route('alojamientos.store'), [
            'title'                  => 'Habitación cualquiera',
            'type'                   => 'Habitación',
            'price'                  => 450000,
            'price_period'           => 'mes',
            'guests'                 => 1,
            'city'                   => 'Montería',
            'description'            => 'Una descripción suficientemente larga para pasar.',
            'universidad_referencia' => 'Hogwarts',
            'phone'                  => '3001234567',
            'photos'                 => [\Illuminate\Http\UploadedFile::fake()->image('cuarto.jpg')],
        ])->assertSessionHasErrors('universidad_referencia');
    }
}
