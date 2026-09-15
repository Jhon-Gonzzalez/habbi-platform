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

    public function view(?User $user, Alojamiento $alojamiento): bool
    {
        return $alojamiento->is_active || ($user && $user->id === $alojamiento->user_id);
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
        return $user->id !== $alojamiento->user_id && $alojamiento->is_active;
    }
}
