<?php

namespace App\Services;

use App\Models\Alojamiento;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Conversaciones entre estudiante y arrendador, y el acuerdo que las cierra.
 */
class ChatService
{
    /**
     * Abre la conversación o devuelve la que ya había.
     *
     * Una sola por estudiante y alojamiento: si cada mensaje abriera un hilo
     * nuevo, la bandeja del arrendador sería inservible.
     */
    public function abrir(Alojamiento $alojamiento, User $estudiante): Conversacion
    {
        return Conversacion::firstOrCreate(
            [
                'alojamiento_id' => $alojamiento->id,
                'estudiante_id'  => $estudiante->id,
            ],
            ['arrendador_id' => $alojamiento->user_id],
        );
    }

    public function enviar(Conversacion $conversacion, User $autor, string $cuerpo): Mensaje
    {
        return DB::transaction(function () use ($conversacion, $autor, $cuerpo) {
            $mensaje = $conversacion->mensajes()->create([
                'user_id' => $autor->id,
                'cuerpo'  => $cuerpo,
            ]);

            $conversacion->forceFill(['ultimo_mensaje_en' => now()])->save();

            return $mensaje;
        });
    }

    /** Un aviso escrito por la plataforma, no por ninguna de las dos partes. */
    public function avisoDelSistema(Conversacion $conversacion, string $cuerpo): Mensaje
    {
        $mensaje = $conversacion->mensajes()->create([
            'user_id'        => $conversacion->estudiante_id,
            'cuerpo'         => $cuerpo,
            'es_del_sistema' => true,
            'leido_en'       => now(),
        ]);

        $conversacion->forceFill(['ultimo_mensaje_en' => now()])->save();

        return $mensaje;
    }

    /** Una parte propone cerrar el trato; la otra tendrá que confirmarlo. */
    public function proponerAcuerdo(Conversacion $conversacion, User $quien, int $personas): void
    {
        $conversacion->forceFill([
            'acuerdo_propuesto_por' => $quien->id,
            'acuerdo_propuesto_en'  => now(),
            'acuerdo_personas'      => $personas,
        ])->save();

        $this->avisoDelSistema($conversacion, sprintf(
            '%s propuso cerrar el acuerdo por %d %s. La otra persona tiene que confirmarlo.',
            $quien->name,
            $personas,
            $personas === 1 ? 'plaza' : 'plazas',
        ));
    }

    public function retirarAcuerdo(Conversacion $conversacion, User $quien): void
    {
        $conversacion->forceFill([
            'acuerdo_propuesto_por' => null,
            'acuerdo_propuesto_en'  => null,
        ])->save();

        $this->avisoDelSistema($conversacion, $quien->name . ' retiró la propuesta de acuerdo.');
    }

    /**
     * La otra parte confirma: se cierra el trato y la publicación se actualiza.
     *
     * Va en una transacción porque toca dos tablas. Si bajara los cupos y
     * fallara al marcar la conversación, el alojamiento perdería una plaza
     * sin que constara por qué.
     */
    public function confirmarAcuerdo(Conversacion $conversacion, User $quien): void
    {
        DB::transaction(function () use ($conversacion, $quien) {
            $alojamiento = $conversacion->alojamiento()->lockForUpdate()->first();
            $personas    = min($conversacion->acuerdo_personas, $alojamiento->cupos);

            $conversacion->forceFill([
                'acuerdo_confirmado_en' => now(),
                'acuerdo_personas'      => $personas,
            ])->save();

            $alojamiento->decrement('cupos', $personas);
            $alojamiento->refresh();

            $this->avisoDelSistema($conversacion, $this->resumenDelCierre($alojamiento, $personas, $quien));
        });
    }

    private function resumenDelCierre(Alojamiento $alojamiento, int $personas, User $quien): string
    {
        $texto = sprintf(
            'Acuerdo confirmado por %s: %d %s.',
            $quien->name,
            $personas,
            $personas === 1 ? 'plaza' : 'plazas',
        );

        return $alojamiento->cupos === 0
            ? $texto . ' La publicación ya no aparece en el buscador: no quedan plazas libres.'
            : $texto . sprintf(' Quedan %d por arrendar.', $alojamiento->cupos);
    }

    /**
     * El arrendador vuelve a poner plazas a la venta (se fue un inquilino).
     * No revive conversaciones viejas: los acuerdos cerrados se quedan como
     * están, que son el historial de lo que pasó.
     */
    public function reabrir(Alojamiento $alojamiento, int $cupos): void
    {
        $alojamiento->forceFill(['cupos' => $cupos])->save();
    }
}
