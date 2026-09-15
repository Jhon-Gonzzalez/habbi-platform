<?php

namespace Database\Factories;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Alojamiento> */
class AlojamientoFactory extends Factory
{
    protected $model = Alojamiento::class;

    public function definition(): array
    {
        $ciudades = [
            'Bogotá'       => ['Chapinero', 'Teusaquillo', 'La Candelaria', 'Suba'],
            'Medellín'     => ['Laureles', 'El Poblado', 'Belén', 'Robledo'],
            'Cali'         => ['San Fernando', 'Granada', 'Ciudad Jardín'],
            'Bucaramanga'  => ['Cabecera', 'La Floresta', 'Provenza'],
            'Barranquilla' => ['Alto Prado', 'El Prado', 'Riomar'],
        ];

        $ciudad = fake()->randomElement(array_keys($ciudades));
        $tipo   = fake()->randomElement(Alojamiento::TIPOS);

        return [
            'user_id'      => User::factory(),
            'title'        => $tipo . ' ' . fake()->randomElement([
                'amoblado cerca de la universidad',
                'con todos los servicios incluidos',
                'en zona tranquila y segura',
                'ideal para estudiantes',
                'con excelente iluminación natural',
            ]),
            'type'         => $tipo,
            'price'        => fake()->numberBetween(35, 180) * 10000,
            'price_period' => fake()->randomElement(Alojamiento::PERIODOS),
            'guests'       => fake()->numberBetween(1, 4),
            'city'         => $ciudad,
            'neighborhood' => fake()->randomElement($ciudades[$ciudad]),
            'address'      => 'Calle ' . fake()->numberBetween(1, 120) . ' # ' . fake()->numberBetween(1, 90) . '-' . fake()->numberBetween(1, 99),
            'description'  => fake()->paragraphs(3, true),
            'amenities'    => fake()->randomElements(Alojamiento::COMODIDADES, fake()->numberBetween(3, 6)),
            'phone'        => '+57 3' . fake()->numerify('## ### ####'),
            'cover_path'   => null,
            'photos'       => [],
            'is_active'    => true,
        ];
    }

    public function pausado(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
