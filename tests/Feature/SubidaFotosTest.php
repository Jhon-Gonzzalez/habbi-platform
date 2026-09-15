<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Subidas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubidaFotosTest extends TestCase
{
    use RefreshDatabase;

    /** @dataProvider tamanos */
    public function test_convierte_la_notacion_de_php_a_bytes(string $valor, int $esperado): void
    {
        $this->assertSame($esperado, Subidas::aBytes($valor));
    }

    public static function tamanos(): array
    {
        return [
            ['8M',   8 * 1048576],
            ['2M',   2 * 1048576],
            ['512K', 512 * 1024],
            ['1G',   1073741824],
            ['0',    0],
            ['',     0],
        ];
    }

    public function test_el_limite_de_archivo_nunca_supera_al_del_formulario(): void
    {
        $this->assertLessThanOrEqual(Subidas::maxPost(), Subidas::maxArchivo());
    }

    public function test_la_pagina_413_explica_el_problema_sin_necesitar_sesion(): void
    {
        // La vista se renderiza sin sesión ni usuario, como ocurre de verdad
        // cuando ValidatePostSize corta la petición.
        $html = view('errors.413')->render();

        $this->assertStringContainsString('Las fotos pesan demasiado', $html);
        $this->assertStringContainsString('Volver e intentarlo de nuevo', $html);
        $this->assertStringContainsString('tu publicación no llegó a crearse', $html);
    }

    public function test_el_formulario_de_publicar_anuncia_el_limite_al_navegador(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('alojamientos.create'))
            ->assertOk()
            ->assertSee('data-max-bytes="' . Subidas::maxPost() . '"', false);
    }

    public function test_se_pueden_subir_varias_fotos_dentro_del_limite(): void
    {
        Storage::fake('public');

        $fotos = [];
        for ($i = 0; $i < 4; $i++) {
            $fotos[] = UploadedFile::fake()->image("foto{$i}.jpg", 1200, 900)->size(900);
        }

        $this->actingAs(User::factory()->create())
            ->post(route('alojamientos.store'), [
                'title'        => 'Habitacion con cuatro fotos de prueba',
                'type'         => 'Habitación',
                'price'        => 600000,
                'price_period' => 'mes',
                'guests'       => 1,
                'city'         => 'Bogota',
                'description'  => 'Descripcion suficientemente larga para pasar la validacion del formulario.',
                'phone'        => '+57 300 123 4567',
                'photos'       => $fotos,
            ])
            ->assertRedirect();

        $alojamiento = \App\Models\Alojamiento::firstWhere('title', 'Habitacion con cuatro fotos de prueba');

        $this->assertCount(4, $alojamiento->photos);
        foreach ($alojamiento->photos as $ruta) {
            Storage::disk('public')->assertExists($ruta);
        }
    }

    public function test_una_foto_demasiado_pesada_se_rechaza_con_un_mensaje(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post(route('alojamientos.store'), [
                'title'        => 'Habitacion con una foto enorme',
                'type'         => 'Habitación',
                'price'        => 600000,
                'price_period' => 'mes',
                'guests'       => 1,
                'city'         => 'Bogota',
                'description'  => 'Descripcion suficientemente larga para pasar la validacion del formulario.',
                'phone'        => '+57 300 123 4567',
                'photos'       => [UploadedFile::fake()->image('enorme.jpg')->size(6000)], // 6 MB > 5 MB
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('alojamientos', 0);
    }
}
