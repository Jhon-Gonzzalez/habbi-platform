<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El panel después de unificar las dos pantallas de publicaciones y de
 * añadirle buscador, filtros y exportación.
 */
class PanelAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /* ============ Una sola pantalla de publicaciones ============ */

    public function test_las_direcciones_viejas_siguen_funcionando(): void
    {
        // Pueden estar en un marcador del navegador o pegadas en un correo.
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/revision')
            ->assertRedirect('/admin/publicaciones');

        $this->actingAs($admin)->get('/admin/alojamientos')
            ->assertRedirect('/admin/publicaciones?estado=todas');
    }

    public function test_la_pantalla_lista_aprobar_pausar_y_eliminar_en_el_mismo_sitio(): void
    {
        /*
         * Es la razón de haberlas unido: antes aprobar estaba en una
         * pantalla del menú y pausar en otra, con las mismas
         * publicaciones listadas en las dos.
         */
        $pendiente = Alojamiento::factory()->pendiente()->create(['title' => 'Cuarto pendiente de revisar']);
        $aprobada  = Alojamiento::factory()->create(['title' => 'Cuarto ya aprobado']);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.publicaciones.index', ['estado' => 'todas']))
            ->assertOk()
            ->assertSee('Cuarto pendiente de revisar')
            ->assertSee('Cuarto ya aprobado')
            ->assertSee('Eliminar')
            ->assertSee('Pausar');

        // Y las acciones funcionan desde aquí.
        $this->actingAs($admin)->post(route('admin.publicaciones.aprobar', $pendiente))->assertRedirect();
        $this->assertTrue($pendiente->fresh()->estaAprobada());

        $this->actingAs($admin)->patch(route('admin.publicaciones.toggle', $aprobada))->assertRedirect();
        $this->assertFalse($aprobada->fresh()->is_active);
    }

    public function test_solo_se_ofrece_pausar_lo_que_ya_esta_aprobado(): void
    {
        // Pausar algo que nadie ha aprobado todavía no significa nada.
        Alojamiento::factory()->pendiente()->create(['title' => 'Esperando revisión']);

        $this->actingAs($this->admin())
            ->get(route('admin.publicaciones.index'))
            ->assertOk()
            ->assertSee('Esperando revisión')
            ->assertDontSee('Pausar');
    }

    public function test_el_buscador_de_publicaciones_filtra_por_titulo_ciudad_y_barrio(): void
    {
        Alojamiento::factory()->create(['title' => 'Cuarto en La Granja', 'neighborhood' => 'La Granja']);
        Alojamiento::factory()->create(['title' => 'Apartamento en Mocarí', 'neighborhood' => 'Mocarí']);

        $this->actingAs($this->admin())
            ->get(route('admin.publicaciones.index', ['estado' => 'todas', 'q' => 'Granja']))
            ->assertOk()
            ->assertSee('Cuarto en La Granja')
            ->assertDontSee('Apartamento en Mocarí');
    }

    public function test_la_pestana_de_pausadas_muestra_las_aprobadas_que_no_se_ven(): void
    {
        Alojamiento::factory()->pausado()->create(['title' => 'Aprobada pero pausada']);
        Alojamiento::factory()->create(['title' => 'Aprobada y visible']);
        Alojamiento::factory()->pendiente()->pausado()->create(['title' => 'Pendiente y pausada']);

        $this->actingAs($this->admin())
            ->get(route('admin.publicaciones.index', ['estado' => 'pausadas']))
            ->assertOk()
            ->assertSee('Aprobada pero pausada')
            ->assertDontSee('Aprobada y visible')
            // Una pendiente no es «pausada»: todavía no la ha visto nadie.
            ->assertDontSee('Pendiente y pausada');
    }

    /* ============ Avisos accionables del resumen ============ */

    public function test_el_resumen_avisa_de_las_publicaciones_sin_punto_en_el_mapa(): void
    {
        /*
         * Es el fallo más caro porque no se nota: la publicación existe
         * y se ve en el listado, pero nunca aparece cuando alguien
         * busca «a menos de 2 km de la universidad».
         */
        Alojamiento::factory()->create(['lat' => null, 'lng' => null]);

        $this->actingAs($this->admin())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('sin punto en el mapa');
    }

    public function test_sin_problemas_el_resumen_no_inventa_avisos(): void
    {
        $arrendador = User::factory()->arrendador()->create(['verificado_en' => now()]);
        Alojamiento::factory()->for($arrendador)->create([
            'lat' => 8.75, 'lng' => -75.88, 'cover_path' => 'fotos/x.jpg',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee('Qué conviene mirar');
    }

    /* ============ Filtro por rol ============ */

    public function test_se_puede_filtrar_a_los_arrendadores(): void
    {
        User::factory()->arrendador()->create(['name' => 'Dolores Arrendadora']);
        User::factory()->create(['name' => 'Laura Estudiante']);

        $this->actingAs($this->admin())
            ->get(route('admin.usuarios.index', ['rol' => User::ROLE_ARRENDADOR]))
            ->assertOk()
            ->assertSee('Dolores Arrendadora')
            ->assertDontSee('Laura Estudiante');
    }

    /* ============ Exportación ============ */

    public function test_el_csv_de_publicaciones_trae_los_datos(): void
    {
        Alojamiento::factory()->create(['title' => 'Cuarto exportado', 'city' => 'Montería']);

        $respuesta = $this->actingAs($this->admin())
            ->get(route('admin.exportar', 'publicaciones'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $respuesta->streamedContent();

        $this->assertStringContainsString('Cuarto exportado', $csv);
        $this->assertStringContainsString('Montería', $csv);
        // El BOM es lo que evita que Excel en Windows rompa las tildes.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    public function test_el_csv_de_usuarios_no_lleva_contrasenas(): void
    {
        /*
         * Un CSV acaba en la carpeta de descargas, en un correo o en un
         * USB. Un hash filtrado se puede atacar sin prisa y sin que
         * nadie se entere.
         */
        $usuario = User::factory()->create(['name' => 'Laura Hoyos']);

        $csv = $this->actingAs($this->admin())
            ->get(route('admin.exportar', 'usuarios'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Laura Hoyos', $csv);
        $this->assertStringNotContainsString($usuario->password, $csv);
        $this->assertStringNotContainsString('$2y$', $csv);
    }

    public function test_descargar_datos_queda_en_la_auditoria(): void
    {
        // Sacar datos personales de la aplicación tiene que dejar rastro.
        $this->actingAs($this->admin())->get(route('admin.exportar', 'usuarios'))->assertOk();

        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::DATOS_EXPORTADOS]);
    }

    public function test_no_se_puede_exportar_cualquier_cosa(): void
    {
        $this->actingAs($this->admin())->get('/admin/exportar/contrasenas')->assertNotFound();
    }

    public function test_un_usuario_normal_no_exporta_nada(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.exportar', 'usuarios'))
            ->assertForbidden();
    }
}
