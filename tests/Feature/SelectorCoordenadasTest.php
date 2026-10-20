<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El arrendador marca el punto en el mapa al publicar (RF-4, RF-6).
 *
 * No hay geocodificación: lo que se guarda es lo que él señaló. Estas
 * pruebas cubren justamente los bordes de eso — que un par incompleto no
 * entre, y que «no marqué nada» se guarde como nada y no como el punto
 * cero del planeta.
 */
class SelectorCoordenadasTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function formulario(array $extra = []): array
    {
        return array_merge([
            'title'        => 'Habitación amoblada en La Granja',
            'type'         => 'Habitación',
            'price'        => 600000,
            'price_period' => 'mes',
            'guests'       => 1,
            'city'         => 'Montería',
            'neighborhood' => 'La Granja',
            'description'  => 'Habitación amoblada con baño privado, wifi y servicios incluidos, a diez minutos de la universidad.',
            'phone'        => '+57 300 123 4567',
            'photos'       => [UploadedFile::fake()->image('foto.jpg')],
        ], $extra);
    }

    private function publicar(array $extra = [])
    {
        Storage::fake('public');

        return $this->actingAs(User::factory()->arrendador()->create())
            ->post(route('alojamientos.store'), $this->formulario($extra));
    }

    public function test_el_formulario_de_publicacion_trae_el_selector_de_mapa(): void
    {
        $respuesta = $this->actingAs(User::factory()->arrendador()->create())
            ->get(route('alojamientos.create'))
            ->assertOk();

        $respuesta->assertSee('selector-mapa', false);
        $respuesta->assertSee('assets/js/selector-mapa.js', false);
        $respuesta->assertSee('assets/vendor/leaflet/leaflet.js', false);
        $respuesta->assertSee('name="lat"', false);
        $respuesta->assertSee('name="lng"', false);
    }

    public function test_el_punto_marcado_se_guarda(): void
    {
        $this->publicar(['lat' => '8.751234', 'lng' => '-75.881234'])
            ->assertSessionHasNoErrors();

        $a = Alojamiento::firstWhere('title', 'Habitación amoblada en La Granja');

        $this->assertEqualsWithDelta(8.751234, $a->lat, 0.000001);
        $this->assertEqualsWithDelta(-75.881234, $a->lng, 0.000001);
    }

    public function test_sin_marcar_el_punto_se_guarda_vacio_y_no_el_cero(): void
    {
        /*
         * 0,0 es una coordenada real: está en el golfo de Guinea. Si los
         * campos vacíos llegaran como 0, la publicación aparecería en el
         * Atlántico y entraría en el filtro «a menos de 10 km» de nada.
         */
        $this->publicar(['lat' => '', 'lng' => ''])
            ->assertSessionHasNoErrors();

        $a = Alojamiento::firstWhere('title', 'Habitación amoblada en La Granja');

        $this->assertNull($a->lat);
        $this->assertNull($a->lng);
    }

    public function test_media_coordenada_no_se_acepta(): void
    {
        $this->publicar(['lat' => '8.75', 'lng' => ''])
            ->assertSessionHasErrors('lng');

        $this->assertDatabaseCount('alojamientos', 0);
    }

    public function test_una_coordenada_fuera_del_planeta_no_se_acepta(): void
    {
        $this->publicar(['lat' => '95.5', 'lng' => '-75.88'])
            ->assertSessionHasErrors('lat');

        $this->assertDatabaseCount('alojamientos', 0);
    }

    public function test_una_coordenada_que_no_es_un_numero_no_se_acepta(): void
    {
        $this->publicar(['lat' => 'por la universidad', 'lng' => '-75.88'])
            ->assertSessionHasErrors('lat');

        $this->assertDatabaseCount('alojamientos', 0);
    }

    public function test_al_editar_se_puede_quitar_el_punto(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->arrendador()->create();
        $a = Alojamiento::factory()->for($usuario)->create([
            'lat' => 8.75,
            'lng' => -75.88,
        ]);

        $this->actingAs($usuario)
            ->put(route('alojamientos.update', $a), $this->formulario([
                'photos' => null,
                'lat'    => '',
                'lng'    => '',
            ]))
            ->assertSessionHasNoErrors();

        $a->refresh();

        $this->assertNull($a->lat);
        $this->assertNull($a->lng);
    }

    public function test_el_formulario_de_edicion_recuerda_el_punto_guardado(): void
    {
        $usuario = User::factory()->arrendador()->create();
        $a = Alojamiento::factory()->for($usuario)->create([
            'lat' => 8.751234,
            'lng' => -75.881234,
        ]);

        $this->actingAs($usuario)
            ->get(route('alojamientos.edit', $a))
            ->assertOk()
            ->assertSee('8.751234', false)
            ->assertSee('-75.881234', false);
    }
}
