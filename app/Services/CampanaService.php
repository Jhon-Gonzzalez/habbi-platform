<?php

namespace App\Services;

use App\Models\Alojamiento;
use App\Models\Auditoria;
use App\Models\Campana;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Activar y quitar el destaque de pago de una publicación.
 *
 * El cobro es MANUAL: el arrendador paga por Nequi, Daviplata o en
 * efectivo, y un administrador lo registra aquí. No hay pasarela, y eso
 * es deliberado mientras el volumen sea pequeño: integrar una cobra
 * comisión por operación y obliga a facturación electrónica, lo que para
 * veinte cobros al mes cuesta más de lo que deja.
 *
 * Cuando el volumen lo justifique, lo único que cambia es quién llama a
 * activar(): hoy el panel, mañana el aviso de la pasarela. Por eso el
 * registro del cobro vive aquí y no en el controlador.
 */
class CampanaService
{
    public function activar(
        Alojamiento $alojamiento,
        int $dias,
        int $monto,
        string $medio,
        ?string $referencia,
        ?string $nota,
        User $registradoPor,
    ): Campana {
        return DB::transaction(function () use ($alojamiento, $dias, $monto, $medio, $referencia, $nota, $registradoPor) {
            // Si ya estaba destacada, los días nuevos se suman a los que le
            // quedaban: renovar antes de tiempo no puede costarle días.
            $empieza = $alojamiento->tieneCampanaActiva() ? $alojamiento->campana_hasta->copy() : now();

            $alojamiento->activarCampana($dias);

            $cobro = $alojamiento->cobrosDeCampana()->create([
                'registrado_por' => $registradoPor->id,
                'dias'           => $dias,
                'monto'          => $monto,
                'medio'          => $medio,
                'referencia'     => $referencia,
                'nota'           => $nota,
                'empieza_en'     => $empieza,
                'termina_en'     => $alojamiento->campana_hasta,
            ]);

            Auditor::registrar(
                Auditoria::CAMPANA_ACTIVADA,
                sprintf(
                    'Destacó «%s» por %d día(s) · %s por %s',
                    $alojamiento->title,
                    $dias,
                    $cobro->montoFormateado(),
                    $medio,
                ),
                $alojamiento,
                [
                    'dias'       => $dias,
                    'monto'      => $monto,
                    'medio'      => $medio,
                    'referencia' => $referencia,
                    'hasta'      => $alojamiento->campana_hasta?->toDateTimeString(),
                ],
                $registradoPor,
            );

            return $cobro;
        });
    }

    /**
     * Quita el destaque antes de tiempo.
     *
     * NO borra el cobro: el dinero entró y tiene que seguir en el
     * historial aunque la publicación deje de destacarse. Si hay que
     * devolverlo, eso se resuelve fuera y queda la nota en la auditoría.
     */
    public function quitar(Alojamiento $alojamiento, User $actor, ?string $motivo = null): void
    {
        $alojamiento->terminarCampana();

        Auditor::registrar(
            Auditoria::CAMPANA_TERMINADA,
            sprintf('Quitó el destaque de «%s»', $alojamiento->title),
            $alojamiento,
            array_filter(['motivo' => $motivo]),
            $actor,
        );
    }
}
