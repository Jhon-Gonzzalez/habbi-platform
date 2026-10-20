<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea la cuenta de administrador inicial.
 * Las credenciales se leen del .env para no dejarlas escritas en el repositorio.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Se usa ?: y no el segundo argumento de env().
         *
         * env('X', 'defecto') solo aplica el defecto cuando la variable NO
         * EXISTE. El .env.example trae «ADMIN_EMAIL=» vacío, así que con el
         * defecto normal se creaba un administrador con el correo en blanco:
         * existía en la base de datos y era imposible iniciar sesión con él.
         */
        $email = env('ADMIN_EMAIL') ?: 'admin@habbi.test';

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => env('ADMIN_NAME') ?: 'Administrador HABBI',
                'role'              => User::ROLE_ADMIN,
                'email_verified_at' => now(),
                'password'          => Hash::make(env('ADMIN_PASSWORD') ?: 'habbi.admin2026'),
            ],
        );

        // Un administrador con correo vacío no sirve para nada y estorba.
        User::where('role', User::ROLE_ADMIN)->where('email', '')->delete();

        $this->command->info("Administrador listo: {$admin->email}");
    }
}
