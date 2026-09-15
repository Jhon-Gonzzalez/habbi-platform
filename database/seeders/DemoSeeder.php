<?php

namespace Database\Seeders;

use App\Models\Alojamiento;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos de ejemplo para desarrollo y demostraciones.
 * No lo ejecutes en producción: php artisan db:seed --class=AdminSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->warn('DemoSeeder omitido en producción.');

            return;
        }

        $arrendadores = User::factory()->count(6)->create();
        $estudiantes  = User::factory()->count(12)->create();

        $arrendadores->each(function (User $arrendador) use ($estudiantes) {
            Alojamiento::factory()
                ->count(fake()->numberBetween(1, 4))
                ->for($arrendador)
                ->create()
                ->each(function (Alojamiento $alojamiento) use ($estudiantes) {
                    $estudiantes
                        ->random(fake()->numberBetween(0, 5))
                        ->each(fn (User $estudiante) => Rating::factory()->create([
                            'user_id'        => $estudiante->id,
                            'alojamiento_id' => $alojamiento->id,
                        ]));
                });
        });

        $this->command->info('Datos de demostración creados.');
    }
}
