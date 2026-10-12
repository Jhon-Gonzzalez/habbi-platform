<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Auditoria;
use App\Models\Rating;
use App\Models\Reporte;
use App\Models\SolicitudArrendador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    /* ===================== Qué se registra ===================== */

    public function test_registra_cuando_un_admin_borra_la_resena_de_otro(): void
    {
        $admin  = User::factory()->admin()->create(['name' => 'Jhon Admin']);
        $rating = Rating::factory()->create(['rating' => 1, 'comment' => 'Pésimo lugar']);

        $this->actingAs($admin)->delete(route('ratings.destroy', $rating));

        $registro = Auditoria::sole();
        $this->assertSame(Auditoria::RESENA_BORRADA, $registro->accion);
        $this->assertSame('Jhon Admin', $registro->actor_nombre);
        // El contenido borrado queda guardado: si fue un abuso, se puede ver qué decía.
        $this->assertSame('Pésimo lugar', $registro->datos['comentario']);
    }

    public function test_no_registra_cuando_alguien_borra_su_propia_resena(): void
    {
        // Borrar lo tuyo es normal, no un ejercicio de poder. Si se registrara,
        // el listado se llenaría de ruido y dejaría de servir para vigilar.
        $autor  = User::factory()->create();
        $rating = Rating::factory()->create(['user_id' => $autor->id]);

        $this->actingAs($autor)->delete(route('ratings.destroy', $rating));

        $this->assertDatabaseCount('auditorias', 0);
    }

    public function test_registra_el_borrado_de_un_alojamiento_con_sus_datos(): void
    {
        $admin       = User::factory()->admin()->create();
        $alojamiento = Alojamiento::factory()->create(['title' => 'Cuarto en La Granja']);

        $this->actingAs($admin)->delete(route('admin.alojamientos.destroy', $alojamiento));

        $registro = Auditoria::sole();
        $this->assertSame(Auditoria::ALOJAMIENTO_BORRADO, $registro->accion);
        // El nombre se copia porque el alojamiento ya no existe para consultarlo.
        $this->assertSame('Cuarto en La Granja', $registro->objeto_nombre);
        $this->assertDatabaseMissing('alojamientos', ['id' => $alojamiento->id]);
    }

    public function test_registra_pausar_y_reactivar_con_acciones_distintas(): void
    {
        $admin       = User::factory()->admin()->create();
        $alojamiento = Alojamiento::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.alojamientos.toggle', $alojamiento));
        $this->actingAs($admin)->patch(route('admin.alojamientos.toggle', $alojamiento));

        $acciones = Auditoria::orderBy('id')->pluck('accion')->all();
        $this->assertSame(
            [Auditoria::ALOJAMIENTO_PAUSADO, Auditoria::ALOJAMIENTO_ACTIVADO],
            $acciones
        );
    }

    public function test_registra_el_cambio_de_rol_con_el_valor_anterior(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create(['role' => User::ROLE_ESTUDIANTE]);

        $this->actingAs($admin)->put(route('admin.usuarios.update', $usuario), [
            'name'  => $usuario->name,
            'email' => $usuario->email,
            'role'  => User::ROLE_ARRENDADOR,
        ]);

        $registro = Auditoria::where('accion', Auditoria::USUARIO_EDITADO)->sole();
        $this->assertSame(User::ROLE_ESTUDIANTE, $registro->datos['rol']['antes']);
        $this->assertSame(User::ROLE_ARRENDADOR, $registro->datos['rol']['ahora']);
    }

    public function test_no_registra_nada_si_la_edicion_no_cambio_nada(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.usuarios.update', $usuario), [
            'name'  => $usuario->name,
            'email' => $usuario->email,
            'role'  => $usuario->role,
        ]);

        $this->assertDatabaseCount('auditorias', 0);
    }

    public function test_registra_el_enlace_de_reset_pero_nunca_el_token(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.usuarios.enlace-reset', $usuario));

        $registro = Auditoria::sole();
        $this->assertSame(Auditoria::ENLACE_RESET_GENERADO, $registro->accion);

        // Un token guardado sería una llave usable por cualquier administrador
        // que lea esta tabla. Solo se registra que el enlace se generó.
        $json = json_encode($registro->datos) . $registro->descripcion;
        $this->assertStringNotContainsString('token', mb_strtolower($json));
    }

    public function test_registra_la_aprobacion_y_la_revocacion_de_un_arrendador(): void
    {
        $admin      = User::factory()->admin()->create();
        $solicitud  = SolicitudArrendador::factory()->create();

        $this->actingAs($admin)->post(route('admin.solicitudes.aprobar', $solicitud));
        $this->actingAs($admin)->post(route('admin.usuarios.revocar', $solicitud->user));

        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::ARRENDADOR_APROBADO]);
        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::ARRENDADOR_REVOCADO]);
    }

    public function test_registra_el_motivo_cuando_se_rechaza_una_solicitud(): void
    {
        $admin     = User::factory()->admin()->create();
        $solicitud = SolicitudArrendador::factory()->create();

        $this->actingAs($admin)->post(route('admin.solicitudes.rechazar', $solicitud), [
            'motivo' => 'La cédula que subiste está borrosa y no se lee el número.',
        ]);

        $registro = Auditoria::where('accion', Auditoria::ARRENDADOR_RECHAZADO)->sole();
        $this->assertStringContainsString('borrosa', $registro->datos['motivo']);
    }

    public function test_registra_el_cierre_de_un_reporte(): void
    {
        $admin   = User::factory()->admin()->create();
        $reporte = Reporte::factory()->create();

        $this->actingAs($admin)->post(route('admin.reportes.resolver', $reporte), [
            'estado' => Reporte::ESTADO_ATENDIDO,
        ]);

        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::REPORTE_RESUELTO]);
    }

    /* ===================== El registro no se puede limpiar ===================== */

    public function test_el_rastro_sobrevive_al_borrado_de_la_cuenta_del_admin(): void
    {
        $admin  = User::factory()->admin()->create(['name' => 'Admin Fugaz', 'email' => 'fugaz@habbi.test']);
        $otro   = User::factory()->admin()->create();
        $rating = Rating::factory()->create();

        $this->actingAs($admin)->delete(route('ratings.destroy', $rating));
        $this->actingAs($otro)->delete(route('admin.usuarios.destroy', $admin));

        $registro = Auditoria::where('accion', Auditoria::RESENA_BORRADA)->sole();
        $this->assertNull($registro->user_id);       // la relación se suelta
        $this->assertSame('Admin Fugaz', $registro->actor_nombre);  // el nombre queda
        $this->assertSame('fugaz@habbi.test', $registro->actor_correo);
    }

    public function test_no_existe_ninguna_ruta_para_borrar_ni_editar_la_auditoria(): void
    {
        $rutas = collect(app('router')->getRoutes())
            ->map(fn ($r) => $r->methods()[0] . ' ' . $r->uri())
            ->filter(fn ($r) => str_contains($r, 'auditoria'))
            ->values()
            ->all();

        $this->assertSame(['GET admin/auditoria'], $rutas);
    }

    public function test_un_fallo_al_auditar_no_tumba_la_accion(): void
    {
        // Perder una línea de auditoría es malo; dejar a medias el borrado de
        // una reseña porque no se pudo escribir esa línea es peor.
        $admin  = User::factory()->admin()->create();
        $rating = Rating::factory()->create();

        \Illuminate\Support\Facades\Schema::drop('auditorias');

        $this->actingAs($admin)
            ->delete(route('ratings.destroy', $rating))
            ->assertRedirect();

        $this->assertDatabaseMissing('ratings', ['id' => $rating->id]);
    }

    /* ===================== La pantalla ===================== */

    public function test_un_usuario_normal_no_ve_la_auditoria(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.auditoria.index'))
            ->assertForbidden();
    }

    public function test_el_admin_ve_el_listado_con_lo_que_paso(): void
    {
        $admin  = User::factory()->admin()->create();
        $rating = Rating::factory()->create();

        $this->actingAs($admin)->delete(route('ratings.destroy', $rating));

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('Borró una reseña');
    }

    public function test_se_puede_filtrar_por_accion(): void
    {
        $admin       = User::factory()->admin()->create();
        $alojamiento = Alojamiento::factory()->create(['title' => 'Alojamiento despublicado']);
        $rating      = Rating::factory()->create();

        $this->actingAs($admin)->patch(route('admin.alojamientos.toggle', $alojamiento));
        $this->actingAs($admin)->delete(route('ratings.destroy', $rating));

        // Se comprueba contra la DESCRIPCIÓN de la fila, no contra la etiqueta
        // de la acción: esa aparece también en el desplegable del filtro y
        // haría pasar el test sin que la fila estuviera oculta.
        $this->actingAs($admin)
            ->get(route('admin.auditoria.index', ['accion' => Auditoria::ALOJAMIENTO_PAUSADO]))
            ->assertOk()
            ->assertSee('Alojamiento despublicado')
            ->assertDontSee('Borró la reseña de');
    }

    public function test_el_listado_vacio_no_revienta(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('No hay nada registrado todavía');
    }
}
