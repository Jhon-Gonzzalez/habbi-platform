<?php

namespace App\Policies;

use App\Models\Alojamiento;
use App\Models\User;

class AlojamientoPolicy
{
    /** Un administrador puede hacer cualquier cosa sobre cualquier alojamiento. */
    public function before(User $user, string $ability): ?bool
    {
        // Calificar es la excepción: ni un admin puede reseñar su propia publicación.
        if ($ability === 'rate') {
            return null;
        }

        return $user->isAdmin() ? true : null;
    }

    /**
     * Quién puede abrir la ficha.
     *
     * El dueño siempre, para poder ver cómo quedó y corregirla mientras se
     * revisa. Un administrador también (lo resuelve before()). Cualquier
     * otro, solo si está aprobada y activa; si no, ve un 404.
     */
    public function view(?User $user, Alojamiento $alojamiento): bool
    {
        if ($user && $user->id === $alojamiento->user_id) {
            return true;
        }

        return $alojamiento->estaAprobada() && $alojamiento->is_active;
    }

    public function update(User $user, Alojamiento $alojamiento): bool
    {
        return $user->id === $alojamiento->user_id;
    }

    public function delete(User $user, Alojamiento $alojamiento): bool
    {
        return $user->id === $alojamiento->user_id;
    }

    /** Nadie puede calificar su propio alojamiento. */
    public function rate(User $user, Alojamiento $alojamiento): bool
    {
        return $user->id !== $alojamiento->user_id
            && $alojamiento->is_active
            && $alojamiento->estaAprobada();
    }
}
