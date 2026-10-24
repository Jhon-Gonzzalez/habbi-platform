<?php

namespace Tests\Feature;

use App\Models\Ajuste;
use App\Models\Alojamiento;
use App\Models\Auditoria;
use App\Models\Campana;
use App\Models\TarifaCampana;
use App\Models\User;
use Database\Seeders\TarifaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tarifas de campaña y datos de cobro.
 *
 * Lo que de verdad se comprueba aquí: que el administrador pueda cambiar
 * los precios sin tocar código, y que cambiarlos NO reescriba lo que
 * alguien ya pagó.
 */
class TarifaCampanaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function tarifa(array $extra = []): TarifaCampana
    {
        return TarifaCampana::create(array_merge([
            'nombre' => 'Quincena', 'dias' => 15, 'precio' => 20000,
            'activa' => true, 'destacada' => false, 'orden' => 1,
        ], $extra));
    }

    /* ===================== El administrador manda ===================== */

    public function test_el_admin_crea_una_tarifa_sin_tocar_codigo(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.tarifas.store'), [
                'nombre' => 'Fin de semana', 'dias' => 3, 'precio' => 6000,
                'orden' => 0, 'activa' => 1,
            ])
            ->assertRedirect();

        $tarifa = TarifaCampana::firstWhere('nombre', 'Fin de semana');

        $this->assertNotNull($tarifa);
        $this->assertSame(3, $tarifa->dias);
        $this->assertSame(6000, $tarifa->precio);
        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::TARIFAS_CAMBIADAS]);
    }

    public function test_el_admin_sube_el_precio_para_la_temporada(): void
    {
        $tarifa = $this->tarifa();

        $this->actingAs($this->admin())
            ->put(route('admin.tarifas.update', $tarifa), [
                'nombre' => 'Quincena', 'dias' => 15, 'precio' => 30000,
                'orden' => 1, 'activa' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(30000, $tarifa->fresh()->precio);
    }

    public function test_cambiar_el_precio_no_reescribe_lo_que_ya_se_cobro(): void
    {
        /*
         * Es la prueba que justifica copiar días y monto al cobro en vez
         * de apuntar a la tarifa. Si el cobro guardara solo el id, subir
         * el precio en enero cambiaría de golpe el historial entero y las
         * cuentas del año pasado dejarían de cuadrar.
         */
        $tarifa = $this->tarifa();
        $a = Alojamiento::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.campanas.store'), [
            'alojamiento_id' => $a->id,
            'tarifa_id'      => $tarifa->id,
            'medio'          => 'Nequi',
        ])->assertRedirect();

        $cobro = Campana::first();
        $this->assertSame(20000, $cobro->monto);

        $tarifa->update(['precio' => 50000]);

        $this->assertSame(20000, $cobro->fresh()->monto);
    }

    public function test_borrar_una_tarifa_no_borra_los_cobros_hechos_con_ella(): void
    {
        $tarifa = $this->tarifa();
        $a = Alojamiento::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.campanas.store'), [
            'alojamiento_id' => $a->id, 'tarifa_id' => $tarifa->id, 'medio' => 'Nequi',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.tarifas.destroy', $tarifa))
            ->assertRedirect();

        $this->assertSame(0, TarifaCampana::count());
        $this->assertSame(1, Campana::count());
        $this->assertSame(20000, Campana::first()->monto);
        $this->assertTrue($a->fresh()->tieneCampanaActiva());
    }

    public function test_una_tarifa_oculta_no_se_le_ofrece_al_arrendador(): void
    {
        $this->tarifa(['nombre' => 'Visible', 'activa' => true]);
        $this->tarifa(['nombre' => 'Guardada para enero', 'activa' => false]);

        $a = Alojamiento::factory()->create();

        $this->actingAs($a->user)
            ->get(route('alojamientos.campana', $a))
            ->assertOk()
            ->assertSee('Visible')
            ->assertDontSee('Guardada para enero');
    }

    /* ===================== El cobro ===================== */

    public function test_se_puede_cobrar_a_medida_sin_tarifa(): void
    {
        // Un descuento, un pago a medias o una campaña de regalo en
        // temporada baja no caben en ninguna tarifa.
        $a = Alojamiento::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.campanas.store'), [
            'alojamiento_id' => $a->id,
            'dias'           => 5,
            'monto'          => 0,
            'medio'          => 'Efectivo',
            'nota'           => 'Regalo de lanzamiento',
        ])->assertRedirect();

        $cobro = Campana::first();

        $this->assertSame(5, $cobro->dias);
        $this->assertSame(0, $cobro->monto);
        $this->assertTrue($a->fresh()->tieneCampanaActiva());
    }

    public function test_sin_tarifa_y_sin_dias_no_se_registra_nada(): void
    {
        $a = Alojamiento::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.campanas.store'), [
                'alojamiento_id' => $a->id,
                'medio'          => 'Nequi',
            ])
            ->assertSessionHasErrors(['dias', 'monto']);

        $this->assertSame(0, Campana::count());
    }

    public function test_los_valores_escritos_a_mano_mandan_sobre_la_tarifa(): void
    {
        $tarifa = $this->tarifa();
        $a = Alojamiento::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.campanas.store'), [
            'alojamiento_id' => $a->id,
            'tarifa_id'      => $tarifa->id,
            'monto'          => 10000,   // le hizo descuento
            'medio'          => 'Nequi',
        ])->assertRedirect();

        $cobro = Campana::first();

        $this->assertSame(10000, $cobro->monto);
        $this->assertSame(15, $cobro->dias);   // los días sí salen de la tarifa
    }

    /* ===================== Datos de cobro ===================== */

    public function test_el_admin_cambia_el_numero_de_nequi_desde_el_panel(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cobro.guardar'), [
                'cobro_nequi'  => '300 555 1234',
                'cobro_correo' => 'cobros@habbi.test',
            ])
            ->assertRedirect();

        $this->assertSame('300 555 1234', Ajuste::obtener(Ajuste::COBRO_NEQUI));

        $a = Alojamiento::factory()->create();

        $this->actingAs($a->user)
            ->get(route('alojamientos.campana', $a))
            ->assertOk()
            ->assertSee('300 555 1234');
    }

    public function test_la_auditoria_no_guarda_los_numeros_de_cobro(): void
    {
        /*
         * Queda que alguien los cambió y cuándo, que es lo que sirve para
         * detectar que se coló una cuenta ajena. Copiar los números ahí
         * solo los duplicaría en un sitio más del que nadie se acuerda.
         */
        $this->actingAs($this->admin())->post(route('admin.cobro.guardar'), [
            'cobro_nequi' => '300 555 1234',
        ]);

        $linea = Auditoria::where('accion', Auditoria::COBRO_CONFIGURADO)->first();

        $this->assertNotNull($linea);
        $this->assertStringNotContainsString('300 555 1234', $linea->descripcion);
        $this->assertStringNotContainsString('300 555 1234', json_encode($linea->datos));
    }

    public function test_un_ajuste_guardado_en_blanco_vale_lo_mismo_que_no_tenerlo(): void
    {
        // El mismo problema que da env() con una clave vacía, y que ya
        // mordió dos veces en este proyecto.
        Ajuste::guardar(Ajuste::COBRO_NEQUI, '');

        $this->assertSame('sin número', Ajuste::obtener(Ajuste::COBRO_NEQUI, 'sin número'));
    }

    public function test_un_correo_invalido_no_se_guarda(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cobro.guardar'), ['cobro_correo' => 'esto no es un correo'])
            ->assertSessionHasErrors('cobro_correo');
    }

    public function test_un_usuario_normal_no_toca_las_tarifas(): void
    {
        $tarifa = $this->tarifa();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.tarifas.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.tarifas.destroy', $tarifa))
            ->assertForbidden();

        $this->assertSame(1, TarifaCampana::count());
    }

    /* ===================== El arranque ===================== */

    public function test_una_instalacion_nueva_trae_tarifas_de_partida(): void
    {
        (new TarifaSeeder())->run();

        $this->assertSame(3, TarifaCampana::activas()->count());
        $this->assertNotNull(TarifaCampana::firstWhere('nombre', 'Quincena'));
    }

    public function test_pasar_el_seeder_dos_veces_no_duplica_la_lista_de_precios(): void
    {
        (new TarifaSeeder())->run();
        TarifaCampana::firstWhere('nombre', 'Quincena')->update(['precio' => 99000]);
        (new TarifaSeeder())->run();

        $this->assertSame(3, TarifaCampana::count());
        // Y no pisa lo que el administrador ya había ajustado.
        $this->assertSame(99000, TarifaCampana::firstWhere('nombre', 'Quincena')->precio);
    }

    public function test_el_precio_por_dia_ayuda_a_comparar_planes(): void
    {
        $semana = $this->tarifa(['nombre' => 'Semana', 'dias' => 7, 'precio' => 12000]);
        $mes    = $this->tarifa(['nombre' => 'Mes', 'dias' => 30, 'precio' => 35000]);

        $this->assertSame(1714, $semana->precioPorDia());
        $this->assertSame(1167, $mes->precioPorDia());
    }
}
