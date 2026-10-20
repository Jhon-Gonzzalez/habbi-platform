<?php

namespace Database\Factories;

use App\Models\Alojamiento;
use App\Models\Conversacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Conversacion> */
class ConversacionFactory extends Factory
{
    protected $model = Conversacion::class;

    public function definition(): array
    {
        $alojamiento = Alojamiento::factory()->create();

        return [
            'alojamiento_id'    => $alojamiento->id,
            'estudiante_id'     => User::factory(),
            'arrendador_id'     => $alojamiento->user_id,
            'ultimo_mensaje_en' => now(),
        ];
    }
}
