<?php

namespace Database\Factories;

use App\Models\Alojamiento;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Rating> */
class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'alojamiento_id' => Alojamiento::factory(),
            'rating'         => fake()->numberBetween(3, 5),
            'comment'        => fake()->optional(0.8)->sentence(14),
        ];
    }
}
