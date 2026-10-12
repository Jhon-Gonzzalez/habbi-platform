<?php

namespace Database\Factories;

use App\Models\Alojamiento;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Reporte> */
class ReporteFactory extends Factory
{
    protected $model = Reporte::class;

    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'alojamiento_id' => Alojamiento::factory(),
            'motivo'         => fake()->randomElement(array_keys(Reporte::MOTIVOS)),
            'detalle'        => fake()->sentence(12),
            'estado'         => Reporte::ESTADO_ABIERTO,
        ];
    }

    public function atendido(): static
    {
        return $this->state(fn () => [
            'estado'       => Reporte::ESTADO_ATENDIDO,
            'revisado_por' => User::factory()->admin(),
            'revisado_en'  => now(),
        ]);
    }

    public function descartado(): static
    {
        return $this->state(fn () => [
            'estado'       => Reporte::ESTADO_DESCARTADO,
            'revisado_por' => User::factory()->admin(),
            'revisado_en'  => now(),
        ]);
    }
}
