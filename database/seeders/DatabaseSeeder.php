<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,

            // Las tarifas van antes que los datos de ejemplo: son
            // configuración del negocio, no datos de prueba, y se
            // quedan también en producción.
            TarifaSeeder::class,

            MonteriaSeeder::class,
        ]);
    }
}
