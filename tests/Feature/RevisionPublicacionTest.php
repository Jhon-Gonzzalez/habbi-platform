<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** RF-2, RF-10, CU-02 y CU-07: la publicación se revisa antes de verse. */
class RevisionPublicacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->flush();
    }

    private function publicar(User $arrendador): Alojamiento
    {
        Storage::fake('public');

        $this->actingAs($arrendador)->post(route('alojamientos.store'), [
            'title'        => 'Habitación nueva en La Granja',
            'type'         => 'Habitación',
            'price'        => 450000,
            'price_period' => 'mes',
            'guests'       => 1,
            'city'         => 'Montería',
            'description'  => 'Habitación amplia con baño privado y wifi incluido.',
            'phone'        => '3001234567',
            'photos'       => [UploadedFile::fake()->image('cuarto.jpg')],
        ])->assertSessionHasNoErrors();

        return Alojamiento::latest('id')->first();
    }

    /* ===================== Nace pendiente ===================== */

    public function test_una_publicacion_nueva_nace_pendiente(): void
    {
        $a = $this->publicar(User::factory()->arrendador()->create());

        $this->assertTrue($a->estaPendiente());
        $this->assertFalse($a->esVisibleAlPublico());
    }

    public function test_una_publicacion_pendiente_no_sale_en_el_buscador(): void
    {
        Alojamiento::factory()->pendiente()->create(['title' => 'Cuarto sin revisar']);

        $this->get(route('alojamientos.index'))
            ->assertOk()
            ->assertDontSee('Cuarto sin revisar');
    }

    public function test_un_extrano_no_puede_abrir_una_pendiente(): void
    {
        $a = Alojamiento::factory()->pendiente()->create();

        $this->get(route('alojamientos.show', $a))->assertNotFound();
        $this->actingAs(User::factory()->create())
            ->get(route('alojamientos.show', $a))->assertNotFound();
    }

    public function test_el_dueno_si_puede_verla_mientras_se_revisa(): void
    {
        // Si no pudiera, no sabría cómo quedó ni podría corregirla.
        $arrendador = User::factory()->arrendador()->create();
        $a = Alojamiento::factory()->pendiente()->for($arrendador)->create();

        $this->actingAs($arrendador)->get(route('alojamientos.show', $a))->assertOk();
    }

    public function test_nadie_puede_resenar_una_publicacion_sin_aprobar(): void
    {
        $a = Alojamiento::factory()->pendiente()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('ratings.store', $a), ['rating' => 5])
            ->assertForbidden();
    }

    /* ===================== El administrador decide ===================== */

    public function test_el_admin_aprueba_y_entonces_aparece(): void
    {
        $admin = User::factory()->admin()->create();
        $a     = Alojamiento::factory()->pendiente()->create(['title' => 'Cuarto por aprobar']);

        $this->actingAs($admin)->post(route('admin.revision.aprobar', $a))->assertRedirect();

        $this->assertTrue($a->fresh()->estaAprobada());
        $this->get(route('alojamientos.index'))->assertSee('Cuarto por aprobar');
    }

    public function test_al_rechazar_hace_falta_un_motivo_que_el_arrendador_pueda_leer(): void
    {
        $a = Alojamiento::factory()->pendiente()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.revision.rechazar', $a), ['motivo' => 'no'])
            ->assertSessionHasErrors('motivo');

        $this->assertTrue($a->fresh()->estaPendiente());
    }

    public function test_el_arrendador_ve_el_motivo_del_rechazo(): void
    {
        $arrendador = User::factory()->arrendador()->create();
        $a = Alojamiento::factory()->pendiente()->for($arrendador)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.revision.rechazar', $a), [
                'motivo' => 'La dirección que pusiste no existe en Montería. Revísala.',
            ]);

        $this->actingAs($arrendador)
            ->get(route('alojamientos.mine'))
            ->assertOk()
            ->assertSee('La dirección que pusiste no existe en Montería.');
    }

    public function test_un_usuario_normal_no_entra_a_la_cola_de_revision(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.revision.index'))
            ->assertForbidden();
    }

    public function test_aprobar_y_rechazar_quedan_en_la_auditoria(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.revision.aprobar',
            Alojamiento::factory()->pendiente()->create()));
        $this->actingAs($admin)->post(route('admin.revision.rechazar',
            Alojamiento::factory()->pendiente()->create()),
            ['motivo' => 'Las fotos son de un catálogo, no del lugar real.']);

        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::ALOJAMIENTO_APROBADO]);
        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::ALOJAMIENTO_RECHAZADO]);
    }

    /* ===================== Qué pasa al editar ===================== */

    public function test_editar_una_rechazada_la_devuelve_a_la_cola(): void
    {
        // Es el camino para corregir y volver a intentarlo.
        $arrendador = User::factory()->arrendador()->create();
        $a = Alojamiento::factory()->rechazada()->for($arrendador)->create();

        $this->actingAs($arrendador)->put(route('alojamientos.update', $a), [
            'title'        => 'Título corregido',
            'type'         => $a->type,
            'price'        => $a->price,
            'price_period' => 'mes',
            'guests'       => 1,
            'city'         => $a->city,
            'description'  => $a->description,
            'phone'        => '3001234567',
        ])->assertSessionHasNoErrors();

        $a->refresh();
        $this->assertTrue($a->estaPendiente());
        $this->assertNull($a->motivo_rechazo);
    }

    public function test_editar_una_aprobada_no_la_esconde_pero_queda_anotado(): void
    {
        /*
         * Decisión discutible y deliberada: si cada edición devolviera la
         * publicación a la cola, corregir una errata la dejaría invisible
         * hasta la siguiente revisión, y en la práctica los arrendadores
         * dejarían de corregir. El riesgo (aprobar algo limpio y luego
         * cambiarlo) se cubre dejando rastro en la auditoría.
         */
        $arrendador = User::factory()->arrendador()->create();
        $a = Alojamiento::factory()->for($arrendador)->create(['price' => 400000]);

        $this->actingAs($arrendador)->put(route('alojamientos.update', $a), [
            'title'        => $a->title,
            'type'         => $a->type,
            'price'        => 500000,
            'price_period' => 'mes',
            'guests'       => 1,
            'city'         => $a->city,
            'description'  => $a->description,
            'phone'        => '3001234567',
        ])->assertSessionHasNoErrors();

        $a->refresh();
        $this->assertTrue($a->estaAprobada());
        $this->assertSame(500000, $a->price);
        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::ALOJAMIENTO_EDITADO]);
    }

    public function test_las_publicaciones_que_ya_existian_no_se_escondieron_al_migrar(): void
    {
        // La migración las pasó a «aprobada». Dejarlas pendientes habría
        // ocultado de golpe anuncios que llevaban meses visibles.
        $this->seed(\Database\Seeders\MonteriaSeeder::class);

        $this->assertGreaterThan(0, Alojamiento::disponibles()->count());
    }
}
