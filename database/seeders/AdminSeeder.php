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
        $email = env('ADMIN_EMAIL', 'admin@habbi.test');

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => env('ADMIN_NAME', 'Administrador HABBI'),
                'role'              => User::ROLE_ADMIN,
                'email_verified_at' => now(),
                'password'          => Hash::make(env('ADMIN_PASSWORD', 'habbi.admin2026')),
            ],
        );

        $this->command->info("Administrador listo: {$admin->email}");
    }
}
