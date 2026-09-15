<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlojamientoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_listado_publico_es_accesible_sin_iniciar_sesion(): void
    {
        Alojamiento::factory()->count(3)->create();

        $this->get(route('alojamientos.index'))
            ->assertOk()
            ->assertSee('Buscar alojamiento');
    }

    public function test_el_listado_no_muestra_alojamientos_pausados(): void
    {
        $visible = Alojamiento::factory()->create(['title' => 'Habitacion visible para todos']);
        $oculto  = Alojamiento::factory()->pausado()->create(['title' => 'Habitacion pausada oculta']);

        $this->get(route('alojamientos.index'))
            ->assertSee($visible->title)
            ->assertDontSee($oculto->title);
    }

    public function test_un_invitado_no_puede_abrir_el_formulario_de_publicacion(): void
    {
        $this->get(route('alojamientos.create'))->assertRedirect(route('login'));
    }

    public function test_un_usuario_autenticado_puede_publicar_un_alojamiento(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();

        $respuesta = $this->actingAs($usuario)->post(route('alojamientos.store'), [
            'title'        => 'Apartaestudio amoblado en Chapinero',
            'type'         => 'Apartaestudio',
            'price'        => 850000,
            'price_period' => 'mes',
            'guests'       => 2,
            'city'         => 'Bogota',
            'neighborhood' => 'Chapinero',
            'description'  => 'Apartaestudio completamente amoblado, con servicios incluidos y a diez minutos de la universidad.',
            'amenities'    => ['Wifi', 'Amoblado'],
            'phone'        => '+57 300 123 4567',
            'photos'       => [UploadedFile::fake()->image('foto.jpg')],
        ]);

        $alojamiento = Alojamiento::firstWhere('title', 'Apartaestudio amoblado en Chapinero');

        $this->assertNotNull($alojamiento);
        $respuesta->assertRedirect(route('alojamientos.show', $alojamiento));

        $this->assertSame($usuario->id, $alojamiento->user_id);
        $this->assertCount(1, $alojamiento->photos);
        Storage::disk('public')->assertExists($alojamiento->cover_path);
    }

    public function test_publicar_sin_fotos_falla_la_validacion(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('alojamientos.store'), ['title' => 'Corto'])
            ->assertSessionHasErrors(['photos', 'type', 'price', 'city', 'description', 'phone']);

        $this->assertDatabaseCount('alojamientos', 0);
    }

    public function test_un_usuario_no_puede_editar_el_alojamiento_de_otro(): void
    {
        $intruso     = User::factory()->create();
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs($intruso)
            ->get(route('alojamientos.edit', $alojamiento))
            ->assertForbidden();
    }

    public function test_el_dueno_puede_editar_su_alojamiento(): void
    {
        $alojamiento = Alojamiento::factory()->create();

        $this->actingAs($alojamiento->user)
            ->get(route('alojamientos.edit', $alojamiento))
            ->assertOk();
    }

    public function test_al_eliminar_un_alojamiento_se_borran_sus_fotos_del_disco(): void
    {
        Storage::fake('public');

        $ruta        = UploadedFile::fake()->image('foto.jpg')->store('alojamientos', 'public');
        $alojamiento = Alojamiento::factory()->create(['photos' => [$ruta], 'cover_path' => $ruta]);

        Storage::disk('public')->assertExists($ruta);

        $this->actingAs($alojamiento->user)
            ->delete(route('alojamientos.destroy', $alojamiento))
            ->assertRedirect(route('alojamientos.mine'));

        Storage::disk('public')->assertMissing($ruta);
        $this->assertDatabaseCount('alojamientos', 0);
    }

    public function test_el_dueno_puede_pausar_y_reactivar_su_publicacion(): void
    {
        $alojamiento = Alojamiento::factory()->create(['is_active' => true]);

        $this->actingAs($alojamiento->user)->patch(route('alojamientos.toggle', $alojamiento));
        $this->assertFalse($alojamiento->fresh()->is_active);

        $this->actingAs($alojamiento->user)->patch(route('alojamientos.toggle', $alojamiento));
        $this->assertTrue($alojamiento->fresh()->is_active);
    }

    public function test_el_buscador_filtra_por_ciudad_y_precio(): void
    {
        Alojamiento::factory()->create(['city' => 'Medellin', 'price' => 500000, 'title' => 'Economico en Medellin']);
        Alojamiento::factory()->create(['city' => 'Medellin', 'price' => 2000000, 'title' => 'Costoso en Medellin']);
        Alojamiento::factory()->create(['city' => 'Cali', 'price' => 500000, 'title' => 'Economico en Cali']);

        $this->get(route('alojamientos.index', ['q' => 'Medellin', 'price_max' => 1000000]))
            ->assertSee('Economico en Medellin')
            ->assertDontSee('Costoso en Medellin')
            ->assertDontSee('Economico en Cali');
    }

    public function test_al_editar_se_pueden_quitar_fotos_y_cambiar_la_portada(): void
    {
        Storage::fake('public');

        $foto1 = UploadedFile::fake()->image('uno.jpg')->store('alojamientos', 'public');
        $foto2 = UploadedFile::fake()->image('dos.jpg')->store('alojamientos', 'public');

        $alojamiento = Alojamiento::factory()->create([
            'photos'     => [$foto1, $foto2],
            'cover_path' => $foto1,
        ]);

        $this->actingAs($alojamiento->user)->put(route('alojamientos.update', $alojamiento), [
            'title'         => 'Titulo actualizado del alojamiento',
            'type'          => 'Habitacion' === $alojamiento->type ? 'Estudio' : $alojamiento->type,
            'price'         => 900000,
            'price_period'  => 'mes',
            'guests'        => 2,
            'city'          => 'Bogota',
            'description'   => 'Descripcion actualizada con la longitud minima requerida por el formulario.',
            'phone'         => '+57 300 111 2233',
            'delete_photos' => [$foto1],
            'cover_path'    => $foto2,
            'is_active'     => 1,
        ])->assertRedirect(route('alojamientos.show', $alojamiento));

        $alojamiento->refresh();

        $this->assertSame([$foto2], $alojamiento->photos);
        $this->assertSame($foto2, $alojamiento->cover_path);
        $this->assertSame(900000, $alojamiento->price);

        Storage::disk('public')->assertMissing($foto1);
        Storage::disk('public')->assertExists($foto2);
    }

    public function test_un_usuario_no_puede_borrar_las_fotos_de_otro_alojamiento(): void
    {
        Storage::fake('public');

        $ajena = UploadedFile::fake()->image('ajena.jpg')->store('alojamientos', 'public');
        Alojamiento::factory()->create(['photos' => [$ajena], 'cover_path' => $ajena]);

        $propia      = UploadedFile::fake()->image('propia.jpg')->store('alojamientos', 'public');
        $alojamiento = Alojamiento::factory()->create(['photos' => [$propia], 'cover_path' => $propia]);

        $this->actingAs($alojamiento->user)->put(route('alojamientos.update', $alojamiento), [
            'title'         => 'Intento de borrar una foto ajena',
            'type'          => $alojamiento->type,
            'price'         => 500000,
            'price_period'  => 'mes',
            'guests'        => 1,
            'city'          => 'Cali',
            'description'   => 'Descripcion con la longitud minima requerida por las reglas de validacion.',
            'phone'         => '+57 300 111 2233',
            'delete_photos' => [$ajena],
            'is_active'     => 1,
        ]);

        Storage::disk('public')->assertExists($ajena);
        Storage::disk('public')->assertExists($propia);
    }
}
