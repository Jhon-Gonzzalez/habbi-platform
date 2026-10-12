<?php

namespace Database\Factories;

use App\Models\SolicitudArrendador;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SolicitudArrendador> */
class SolicitudArrendadorFactory extends Factory
{
    protected $model = SolicitudArrendador::class;

    public function definition(): array
    {
        return [
            'user_id'        => User::factory()->estudiante(),
            'estado'         => SolicitudArrendador::PENDIENTE,
            'documento_path' => 'verificaciones/0/documento/' . fake()->uuid() . '.jpg',
            'prueba_path'    => 'verificaciones/0/prueba/' . fake()->uuid() . '.jpg',
            'telefono'       => '+57 3' . fake()->numerify('## ### ####'),
            'mensaje'        => fake()->paragraph(3),
        ];
    }

    public function aprobada(): static
    {
        return $this->state(fn () => [
            'estado'      => SolicitudArrendador::APROBADA,
            'revisada_en' => now(),
        ]);
    }

    public function rechazada(): static
    {
        return $this->state(fn () => [
            'estado'         => SolicitudArrendador::RECHAZADA,
            'motivo_rechazo' => 'La foto del documento no se lee con claridad.',
            'revisada_en'    => now(),
        ]);
    }
}
