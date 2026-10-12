<?php

namespace App\Policies;

use App\Models\SolicitudArrendador;
use App\Models\User;

class SolicitudArrendadorPolicy
{
    /** Solo un administrador revisa solicitudes ajenas. */
    public function revisar(User $user, SolicitudArrendador $solicitud): bool
    {
        return $user->isAdmin() && $solicitud->estaPendiente();
    }

    /** Ver los documentos adjuntos: el dueño o un administrador. */
    public function verDocumentos(User $user, SolicitudArrendador $solicitud): bool
    {
        return $user->isAdmin() || $user->id === $solicitud->user_id;
    }

    /**
     * Puede enviar una solicitud quien todavía no publica y no tiene
     * ninguna esperando revisión.
     */
    public function crear(User $user): bool
    {
        return !$user->puedePublicar() && !$user->tieneSolicitudPendiente();
    }
}
