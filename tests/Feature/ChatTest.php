<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Conversacion;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->flush();
    }

    /** @return array{0: Alojamiento, 1: User, 2: User} */
    private function escenario(int $cupos = 1): array
    {
        $arrendador  = User::factory()->arrendador()->create();
        $alojamiento = Alojamiento::factory()->for($arrendador)->create(['cupos' => $cupos]);

        return [$alojamiento, $arrendador, User::factory()->create()];
    }

    /* ===================== Abrir y escribir ===================== */

    public function test_un_estudiante_abre_la_conversacion_desde_la_ficha(): void
    {
        [$alojamiento, , $estudiante] = $this->escenario();

        $this->actingAs($estudiante)
            ->post(route('mensajes.abrir', $alojamiento))
            ->assertRedirect();

        $this->assertDatabaseHas('conversaciones', [
            'alojamiento_id' => $alojamiento->id,
            'estudiante_id'  => $estudiante->id,
            'arrendador_id'  => $alojamiento->user_id,
        ]);
    }

    public function test_escribir_dos_veces_no_abre_dos_hilos(): void
    {
        // Si cada mensaje abriera un hilo, la bandeja del arrendador sería
        // inservible.
        [$alojamiento, , $estudiante] = $this->escenario();

        $this->actingAs($estudiante)->post(route('mensajes.abrir', $alojamiento));
        $this->actingAs($estudiante)->post(route('mensajes.abrir', $alojamiento));

        $this->assertDatabaseCount('conversaciones', 1);
    }

    public function test_el_arrendador_no_se_escribe_a_si_mismo(): void
    {
        [$alojamiento, $arrendador] = $this->escenario();

        $this->actingAs($arrendador)->post(route('mensajes.abrir', $alojamiento));

        $this->assertDatabaseCount('conversaciones', 0);
    }

    public function test_los_dos_pueden_escribir_y_el_hilo_queda_en_orden(): void
    {
        [$alojamiento, $arrendador, $estudiante] = $this->escenario();
        $conv = app(ChatService::class)->abrir($alojamiento, $estudiante);

        $this->actingAs($estudiante)->post(route('mensajes.enviar', $conv), ['cuerpo' => '¿Sigue disponible?']);
        $this->actingAs($arrendador)->post(route('mensajes.enviar', $conv), ['cuerpo' => 'Sí, puedes verlo mañana.']);

        $this->assertSame(
            ['¿Sigue disponible?', 'Sí, puedes verlo mañana.'],
            $conv->fresh()->mensajes->pluck('cuerpo')->all(),
        );
    }

    public function test_un_tercero_no_puede_leer_la_conversacion(): void
    {
        [$alojamiento, , $estudiante] = $this->escenario();
        $conv = app(ChatService::class)->abrir($alojamiento, $estudiante);

        $this->actingAs(User::factory()->create())
            ->get(route('mensajes.show', $conv))
            ->assertForbidden();
    }

    public function test_ni_siquiera_un_administrador_lee_conversaciones_ajenas(): void
    {
        // Una conversación privada no es material de moderación mientras
        // nadie la reporte.
        [$alojamiento, , $estudiante] = $this->escenario();
        $conv = app(ChatService::class)->abrir($alojamiento, $estudiante);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('mensajes.show', $conv))
            ->assertForbidden();
    }

    public function test_abrir_el_hilo_marca_como_leidos_los_del_otro(): void
    {
        [$alojamiento, $arrendador, $estudiante] = $this->escenario();
        $conv = app(ChatService::class)->abrir($alojamiento, $estudiante);
        app(ChatService::class)->enviar($conv, $arrendador, 'Hola, sigue libre.');

        $this->assertSame(1, $estudiante->mensajesSinLeer());

        $this->actingAs($estudiante)->get(route('mensajes.show', $conv))->assertOk();

        $this->assertSame(0, $estudiante->fresh()->mensajesSinLeer());
    }

    /* ===================== El acuerdo ===================== */

    public function test_el_acuerdo_lo_confirma_la_otra_parte_no_quien_lo_propone(): void
    {
        // La regla central. Si quien propone pudiera confirmar, un arrendador
        // daría por cerrado un trato que el estudiante nunca aceptó y la
        // publicación desaparecería del buscador.
        [$alojamiento, $arrendador, $estudiante] = $this->escenario();
        $conv = app(ChatService::class)->abrir($alojamiento, $estudiante);

        $this->actingAs($arrendador)->post(route('mensajes.acuerdo.proponer', $conv), ['personas' => 1]);

        // El propio proponente intenta confirmarlo: no debe poder.
        $this->actingAs($arrendador)->post(route('mensajes.acuerdo.confirmar', $conv));

        $this->assertNull($conv->fresh()->acuerdo_confirmado_en);
        $this->assertSame(1, $alojamiento->fresh()->cupos);
    }

    public function test_al_confirmar_bajan_los_cupos_y_sale_del_buscador(): void
    {
        [$alojamiento, $arrendador, $estudiante] = $this->escenario(1);
        $conv = app(ChatService::class)->abrir($alojamiento, $estudiante);

        $this->actingAs($arrendador)->post(route('mensajes.acuerdo.proponer', $conv), ['personas' => 1]);
        $this->actingAs($estudiante)->post(route('mensajes.acuerdo.confirmar', $conv));

        $alojamiento->refresh();
        $this->assertSame(0, $alojamiento->cupos);
        $this->assertTrue($alojamiento->estaArrendado());
        $this->assertFalse(Alojamiento::disponibles()->where('id', $alojamiento->id)->exists());
    }

    public function test_con_varias_plazas_solo_baja_las_acordadas(): void
    {
        [$alojamiento, $arrendador, $estudiante] = $this->escenario(4);
        $conv = app(ChatService::class)->abrir($alojamiento, $estudiante);

        $this->actingAs($estudiante)->post(route('mensajes.acuerdo.proponer', $conv), ['personas' => 2]);
        $this->actingAs($arrendador)->post(route('mensajes.acuerdo.confirmar', $conv));

        $this->assertSame(2, $alojamiento->fresh()->cupos);
        $this->assertTrue(Alojamiento::disponibles()->where('id', $alojamiento->id)->exists());
    }

    public function test_no_se_pueden_acordar_mas_plazas_de_las_que_hay(): void
    {
        [$alojamiento, $arrendador, $estudiante] = $this->escenario(2);
        $conv = app(ChatService::class)->abrir($alojamiento, $estudiante);

        $this->actingAs($arrendador)
            ->post(route('mensajes.acuerdo.proponer', $conv), ['personas' => 5])
            ->assertSessionHasErrors('personas');
    }

    public function test_quien_propone_puede_retirar_la_propuesta(): void
    {
        [$alojamiento, $arrendador, $estudiante] = $this->escenario();
        $conv = app(ChatService::class)->abrir($alojamiento, $estudiante);

        $this->actingAs($arrendador)->post(route('mensajes.acuerdo.proponer', $conv), ['personas' => 1]);
        $this->actingAs($arrendador)->post(route('mensajes.acuerdo.retirar', $conv));

        $conv->refresh();
        $this->assertFalse($conv->acuerdoPropuesto());
        $this->assertSame(1, $alojamiento->fresh()->cupos);
    }

    public function test_la_otra_parte_no_puede_retirar_lo_que_no_propuso(): void
    {
        [$alojamiento, $arrendador, $estudiante] = $this->escenario();
        $conv = app(ChatService::class)->abrir($alojamiento, $estudiante);

        $this->actingAs($arrendador)->post(route('mensajes.acuerdo.proponer', $conv), ['personas' => 1]);
        $this->actingAs($estudiante)->post(route('mensajes.acuerdo.retirar', $conv));

        $this->assertTrue($conv->fresh()->acuerdoPropuesto());
    }

    public function test_cada_paso_del_acuerdo_queda_escrito_en_el_hilo(): void
    {
        // Sin esto, el estudiante abre el chat y no entiende por qué la
        // publicación cambió.
        [$alojamiento, $arrendador, $estudiante] = $this->escenario();
        $conv = app(ChatService::class)->abrir($alojamiento, $estudiante);

        $this->actingAs($arrendador)->post(route('mensajes.acuerdo.proponer', $conv), ['personas' => 1]);
        $this->actingAs($estudiante)->post(route('mensajes.acuerdo.confirmar', $conv));

        $avisos = $conv->fresh()->mensajes()->where('es_del_sistema', true)->pluck('cuerpo');

        $this->assertStringContainsString('propuso cerrar el acuerdo', $avisos[0]);
        $this->assertStringContainsString('Acuerdo confirmado', $avisos[1]);
        $this->assertStringContainsString('ya no aparece en el buscador', $avisos[1]);
    }

    public function test_no_se_puede_acordar_sobre_algo_ya_arrendado(): void
    {
        [$alojamiento, $arrendador, $estudiante] = $this->escenario(0);
        $conv = Conversacion::factory()->create([
            'alojamiento_id' => $alojamiento->id,
            'arrendador_id'  => $arrendador->id,
            'estudiante_id'  => $estudiante->id,
        ]);

        $this->actingAs($arrendador)->post(route('mensajes.acuerdo.proponer', $conv), ['personas' => 1]);

        $this->assertNull($conv->fresh()->acuerdo_propuesto_en);
    }

    /* ===================== Ya no se empuja a WhatsApp ===================== */

    public function test_la_ficha_ofrece_el_chat_y_no_whatsapp(): void
    {
        [$alojamiento, , $estudiante] = $this->escenario();

        $html = $this->actingAs($estudiante)
            ->get(route('alojamientos.show', $alojamiento))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Enviar mensaje al arrendador', $html);
        $this->assertStringNotContainsString('wa.me', $html);
    }

    public function test_el_perfil_del_arrendador_ya_no_imprime_el_telefono(): void
    {
        // Esta página lo sacaba en claro, lo que dejaba sin efecto el control
        // de la ficha: bastaba con recorrer perfiles en vez de anuncios.
        $arrendador = User::factory()->arrendador()->create(['phone' => '+57 300 555 0101']);

        $this->actingAs(User::factory()->create())
            ->get(route('arrendadores.perfil', $arrendador))
            ->assertOk()
            ->assertDontSee('300 555 0101')
            ->assertDontSee('wa.me');
    }
}
