<?php

namespace Database\Factories;

use App\Models\Aliado;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Aliado> */
class AliadoFactory extends Factory
{
    protected $model = Aliado::class;

    public function definition(): array
    {
        return [
            'nombre'      => 'Negocio ' . fake()->unique()->numberBetween(1, 9999),
            'descripcion' => 'Algo útil a dos cuadras de la universidad',
            // .test está reservado justamente para esto: nunca va a
            // apuntar a un negocio real por accidente.
            'url'         => 'https://ejemplo.test/' . fake()->slug(2),
            'categoria'   => fake()->randomElement(Aliado::CATEGORIAS),
            'desde'       => today()->subDay(),
            'hasta'       => today()->addMonth(),
            'monto'       => 80000,
            'activo'      => true,
            'orden'       => 0,
        ];
    }

    /** Pagó, pero su periodo ya terminó. */
    public function vencido(): static
    {
        return $this->state(fn () => [
            'desde' => today()->subMonths(2),
            'hasta' => today()->subDay(),
        ]);
    }

    /** Pagado por adelantado: todavía no le toca salir. */
    public function futuro(): static
    {
        return $this->state(fn () => [
            'desde' => today()->addWeek(),
            'hasta' => today()->addMonth(),
        ]);
    }

    public function apagado(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
