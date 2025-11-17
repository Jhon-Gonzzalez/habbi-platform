<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Alojamiento;

class AlojamientoPolicy
{
    public function update(User $user, Alojamiento $a): bool
    {
        return $user->id === $a->user_id;
    }

    public function delete(User $user, Alojamiento $a): bool
    {
        return $user->id === $a->user_id;
    }
}
