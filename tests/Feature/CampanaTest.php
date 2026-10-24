<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Auditoria;
use App\Models\Campana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Publicaciones destacadas de pago.
 *
 * La mitad de estas pruebas no comprueban que el destaque funcione, sino
 * que NO funcione donde no debe: el dinero compra visibilidad cuando el
 * estudiante no pidió un orden, nunca el orden que pidió a mano.
 */
class CampanaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /* ===================== El orden ===================== */

    public function test_la_publicacion_en_campana_sale_primero_en_el_orden_por_defecto(): void
    {
        $normal    = Alojamiento::factory()->create(['title' => 'Habitación normal sin destacar']);
        $destacada = Alojamiento::factory()->enCampana()->create(['title' => 'Habitación que pagó destaque']);

        // La normal se creó antes, así que por «más recientes» iría después
        // de la destacada igualmente: se fuerza lo contrario para que la
        // prueba mida el destaque y no el orden por fecha.
        $normal->update(['created_at' => now()->addHour()]);

        $ids = Alojamiento::disponibles()->filtrar([])->pluck('id');

        $this->assertSame($destacada->id, $ids->first());
        $this->assertTrue($ids->contains($normal->id));
    }

    public function test_la_campana_no_se_salta_el_orden_por_precio(): void
    {
        /*
         * Esta es la prueba importante de todo el módulo. Si el estudiante
         * pidió «precio: menor a mayor», lo más barato va primero aunque
         * alguien haya pagado. Colar ahí un anuncio pagado es mentirle
         * sobre lo que está mirando.
         */
        $barata   = Alojamiento::factory()->create(['price' => 300000, 'title' => 'La más barata, sin pagar']);
        $caraPero = Alojamiento::factory()->enCampana()->create(['price' => 900000, 'title' => 'Cara pero destacada']);

        $ids = Alojamiento::disponibles()->filtrar(['sort' => 'price_asc'])->pluck('id');

        $this->assertSame($barata->id, $ids->first());
        $this->assertSame($caraPero->id, $ids->last());
    }

    public function test_la_campana_no_se_salta_el_orden_por_calificacion(): void
    {
        $destacada = Alojamiento::factory()->enCampana()->create(['title' => 'Destacada sin reseñas']);

        $ids = Alojamiento::disponibles()->conResenas()->filtrar(['sort' => 'rating'])->pluck('id');

        // Con una sola publicación no se puede comparar; lo que importa es
        // que la consulta no reviente y que el destaque no añada un orden
        // extra por delante del pedido.
        $this->assertTrue($ids->contains($destacada->id));

        $sql = Alojamiento::disponibles()->filtrar(['sort' => 'rating'])->toSql();
        $this->assertStringNotContainsString('campana_hasta', $sql);
    }

    public function test_la_campana_no_se_salta_el_filtro_por_distancia(): void
    {
        $uni = Alojamiento::UNIVERSIDADES[0]['coords'];

        $cerca = Alojamiento::factory()->create([
            'lat' => $uni[0] + 0.005, 'lng' => $uni[1], 'title' => 'Cerquita sin pagar',
        ]);
        $lejos = Alojamiento::factory()->enCampana()->create([
            'lat' => $uni[0] + 0.015, 'lng' => $uni[1], 'title' => 'Más lejos pero destacada',
        ]);

        $ids = Alojamiento::disponibles()->filtrar([
            'universidad' => 'Universidad de Córdoba',
            'km'          => 5,
        ])->pluck('id');

        $this->assertSame($cerca->id, $ids->first());
        $this->assertSame($lejos->id, $ids->last());
    }

    public function test_una_campana_terminada_ya_no_sube_a_nadie(): void
    {
        $vencida = Alojamiento::factory()->campanaTerminada()->create([
            'title' => 'Pagó el mes pasado', 'created_at' => now()->subDays(2),
        ]);
        $nueva = Alojamiento::factory()->create([
            'title' => 'Publicada hoy sin pagar', 'created_at' => now(),
        ]);

        $this->assertFalse($vencida->tieneCampanaActiva());
        $this->assertSame($nueva->id, Alojamiento::disponibles()->filtrar([])->pluck('id')->first());
    }

    /* ===================== Las fechas ===================== */

    public function test_renovar_antes_de_tiempo_suma_dias_en_vez_de_perderlos(): void
    {
        $a = Alojamiento::factory()->enCampana(10)->create();
        $antes = $a->campana_hasta->copy();

        $a->activarCampana(15);

        // 10 que le quedaban + 15 nuevos, no 15 a secas.
        $this->assertEqualsWithDelta(
            $antes->addDays(15)->timestamp,
            $a->fresh()->campana_hasta->timestamp,
            5,
        );
    }

    public function test_una_campana_nueva_cuenta_desde_hoy(): void
    {
        $a = Alojamiento::factory()->create();

        $a->activarCampana(15);

        $this->assertEqualsWithDelta(now()->addDays(15)->timestamp, $a->campana_hasta->timestamp, 5);
    }

    /* ===================== La etiqueta ===================== */

    public function test_el_buscador_marca_la_destacada_como_tal(): void
    {
        Alojamiento::factory()->enCampana()->create(['title' => 'Habitación destacada visible']);

        $this->get(route('alojamientos.index'))
            ->assertOk()
            ->assertSee('Destacado');
    }

    public function test_pagar_no_tapa_que_el_alojamiento_esta_pausado(): void
    {
        /*
         * Si el arrendador pausó su publicación, eso es lo que el dueño
         * tiene que ver en «Mis alojamientos», haya pagado o no. El estado
         * real manda sobre el anuncio pagado.
         */
        $usuario = User::factory()->arrendador()->create();
        Alojamiento::factory()->for($usuario)->enCampana()->pausado()->create([
            'title' => 'Pausada aunque destacada',
        ]);

        $this->actingAs($usuario)
            ->get(route('alojamientos.mine'))
            ->assertOk()
            ->assertSee('Pausado')
            ->assertDontSee('★ Destacado', false);
    }

    /* ===================== El cobro ===================== */

    public function test_el_admin_registra_el_cobro_y_arranca_la_campana(): void
    {
        $a = Alojamiento::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.campanas.store'), [
                'alojamiento_id' => $a->id,
                'dias'           => 15,
                'monto'          => 20000,
                'medio'          => 'Nequi',
                'referencia'     => 'M123456789',
            ])
            ->assertRedirect();

        $this->assertTrue($a->fresh()->tieneCampanaActiva());

        $cobro = Campana::first();
        $this->assertSame(20000, $cobro->monto);
        $this->assertSame('Nequi', $cobro->medio);
        $this->assertSame(15, $cobro->dias);

        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::CAMPANA_ACTIVADA]);
    }

    public function test_no_se_puede_hacer_campana_de_una_publicacion_que_no_se_ve(): void
    {
        // Cobrar por subir de posición algo que no sale en el buscador
        // sería cobrar por nada.
        $pendiente = Alojamiento::factory()->pendiente()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.campanas.store'), [
                'alojamiento_id' => $pendiente->id,
                'dias'           => 15,
                'monto'          => 20000,
                'medio'          => 'Nequi',
            ])
            ->assertRedirect();

        $this->assertFalse($pendiente->fresh()->tieneCampanaActiva());
        $this->assertSame(0, Campana::count());
    }

    public function test_terminar_la_campana_no_borra_el_cobro(): void
    {
        // El dinero entró: tiene que seguir en el historial aunque la
        // publicación deje de destacarse.
        $a = Alojamiento::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.campanas.store'), [
            'alojamiento_id' => $a->id, 'dias' => 15, 'monto' => 20000, 'medio' => 'Nequi',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.campanas.destroy', $a))
            ->assertRedirect();

        $this->assertFalse($a->fresh()->tieneCampanaActiva());
        $this->assertSame(1, Campana::count());
        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::CAMPANA_TERMINADA]);
    }

    public function test_un_monto_disparatado_no_se_acepta(): void
    {
        $a = Alojamiento::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.campanas.store'), [
                'alojamiento_id' => $a->id,
                'dias'           => 15,
                'monto'          => 99000000,   // un cero de más
                'medio'          => 'Nequi',
            ])
            ->assertSessionHasErrors('monto');
    }

    public function test_la_lista_de_candidatas_no_incluye_las_pausadas(): void
    {
        /*
         * Mide un fallo concreto: el OR de «sin destaque o con el destaque
         * vencido» sin paréntesis hacía que SQL lo leyera como
         * «(aprobada Y activa) O vencida», y colaba publicaciones pausadas.
         */
        Alojamiento::factory()->pausado()->campanaTerminada()->create(['title' => 'Pausada con destaque vencido']);
        Alojamiento::factory()->create(['title' => 'Activa y disponible']);

        $this->actingAs($this->admin())
            ->get(route('admin.campanas.index'))
            ->assertOk()
            ->assertSee('Activa y disponible')
            ->assertDontSee('Pausada con destaque vencido');
    }

    /* ===================== Permisos ===================== */

    public function test_un_usuario_normal_no_entra_al_panel_de_campanas(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.campanas.index'))
            ->assertForbidden();
    }

    public function test_solo_el_dueno_ve_la_pagina_de_campana_de_su_publicacion(): void
    {
        $a = Alojamiento::factory()->create();

        $this->actingAs($a->user)->get(route('alojamientos.campana', $a))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('alojamientos.campana', $a))->assertForbidden();
    }

    public function test_la_pagina_de_campana_dice_tambien_lo_que_no_hace(): void
    {
        // Prometer de más es lo que hace que nadie renueve.
        $a = Alojamiento::factory()->create();

        $this->actingAs($a->user)
            ->get(route('alojamientos.campana', $a))
            ->assertOk()
            ->assertSee('garantiza arrendar', false)
            ->assertSee('manda lo que él eligió', false);
    }
}
