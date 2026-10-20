<?php

namespace App\Services;

use App\Models\Alojamiento;
use App\Models\Auditoria;
use App\Models\User;

/**
 * Revisión de las publicaciones antes de que se vean.
 *
 * QUÉ PASA AL EDITAR
 * ------------------
 * Una publicación RECHAZADA vuelve a «pendiente» al editarla: ese es el
 * camino para corregir y volver a intentarlo.
 *
 * Una publicación APROBADA se queda aprobada, y esto es una decisión
 * discutible que conviene conocer. Si cada edición la devolviera a la cola,
 * corregir una errata dejaría el anuncio invisible hasta que alguien lo
 * revisara otra vez; el resultado práctico es que los arrendadores dejan de
 * corregir y los precios se quedan viejos, que perjudica al estudiante más
 * que el riesgo que evita.
 *
 * El riesgo existe igualmente: alguien podría aprobar un anuncio limpio y
 * luego editarlo. Por eso cada edición de una publicación ya aprobada queda
 * anotada en la auditoría, donde el administrador puede verla.
 */
class RevisionAlojamientoService
{
    public function aprobar(Alojamiento $alojamiento, User $revisor): void
    {
        $alojamiento->forceFill([
            'estado'         => Alojamiento::APROBADA,
            'motivo_rechazo' => null,
            'revisada_por'   => $revisor->id,
            'revisada_en'    => now(),
        ])->save();

        Auditoria::registrarAprobacion($alojamiento, $revisor);
    }

    public function rechazar(Alojamiento $alojamiento, User $revisor, string $motivo): void
    {
        $alojamiento->forceFill([
            'estado'         => Alojamiento::RECHAZADA,
            'motivo_rechazo' => $motivo,
            'revisada_por'   => $revisor->id,
            'revisada_en'    => now(),
        ])->save();

        Auditoria::registrarRechazo($alojamiento, $revisor, $motivo);
    }

    /**
     * Qué hacer con el estado cuando el arrendador edita.
     *
     * @return bool si la publicación vuelve a la cola de revisión
     */
    public function alEditar(Alojamiento $alojamiento, User $autor): bool
    {
        if ($alojamiento->fueRechazada()) {
            $alojamiento->forceFill([
                'estado'         => Alojamiento::PENDIENTE,
                'motivo_rechazo' => null,
                'revisada_por'   => null,
                'revisada_en'    => null,
            ])->save();

            return true;
        }

        if ($alojamiento->estaAprobada()) {
            Auditoria::registrarEdicionTrasAprobar($alojamiento, $autor);
        }

        return false;
    }
}
