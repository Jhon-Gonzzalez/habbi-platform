<?php

namespace Database\Seeders;

use App\Models\TarifaCampana;
use Illuminate\Database\Seeder;

/**
 * Tarifas de partida para una instalación nueva.
 *
 * Son un punto de partida razonable para Montería, no una verdad: el
 * administrador las cambia desde el panel sin tocar código. Conviene
 * subirlas en enero y julio-agosto, que es cuando los estudiantes
 * buscan, y bajarlas el resto del año.
 *
 * firstOrCreate y no create: si el seeder se vuelve a pasar sobre una
 * base que ya está en uso, no puede duplicar la lista de precios ni
 * pisar los que el administrador haya ajustado.
 */
class TarifaSeeder extends Seeder
{
    public function run(): void
    {
        $tarifas = [
            ['nombre' => 'Una semana', 'dias' => 7,  'precio' => 12000, 'orden' => 1, 'destacada' => false, 'nota' => null],
            ['nombre' => 'Quincena',   'dias' => 15, 'precio' => 20000, 'orden' => 2, 'destacada' => true,  'nota' => 'La más pedida'],
            ['nombre' => 'Un mes',     'dias' => 30, 'precio' => 35000, 'orden' => 3, 'destacada' => false, 'nota' => 'Para temporada de matrículas'],
        ];

        foreach ($tarifas as $tarifa) {
            TarifaCampana::firstOrCreate(
                ['nombre' => $tarifa['nombre']],
                $tarifa + ['activa' => true],
            );
        }
    }
}
