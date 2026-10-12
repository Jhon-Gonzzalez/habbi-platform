<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'role'              => User::ROLE_ESTUDIANTE,
            'phone'             => '+57 3' . fake()->numerify('## ### ####'),
            'password'          => bcrypt('password'),
            'remember_token'    => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role'          => User::ROLE_ADMIN,
            'verificado_en' => now(),
        ]);
    }

    /** Arrendador ya verificado, que es el único que puede publicar. */
    public function arrendador(): static
    {
        return $this->state(fn () => [
            'role'          => User::ROLE_ARRENDADOR,
            'verificado_en' => now(),
        ]);
    }

    /** Estudiante sin verificar: el estado por defecto al registrarse. */
    public function estudiante(): static
    {
        return $this->state(fn () => [
            'role'          => User::ROLE_ESTUDIANTE,
            'verificado_en' => null,
        ]);
    }
}
