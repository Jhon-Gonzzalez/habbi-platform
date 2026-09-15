<?php

namespace App\Policies;

use App\Models\Rating;
use App\Models\User;

class RatingPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function delete(User $user, Rating $rating): bool
    {
        return $user->id === $rating->user_id;
    }
}
