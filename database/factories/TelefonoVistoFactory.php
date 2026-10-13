<?php

namespace Database\Factories;

use App\Models\Alojamiento;
use App\Models\TelefonoVisto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TelefonoVisto> */
class TelefonoVistoFactory extends Factory
{
    protected $model = TelefonoVisto::class;

    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'alojamiento_id' => Alojamiento::factory(),
            'ip'             => fake()->ipv4(),
            'created_at'     => now()->subMinutes(fake()->numberBetween(1, 600)),
        ];
    }
}
