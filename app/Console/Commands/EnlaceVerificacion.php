<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

/**
 * Imprime el enlace de verificación de un usuario en la terminal.
 *
 * En desarrollo los correos no se envían de verdad: van al log. Bucear en
 * storage/logs/laravel.log para sacar el enlace es incómodo y el enlace sale
 * con los «&» escapados como «&amp;», que rompen la firma al pegarlo.
 *
 *   php artisan habbi:enlace tucorreo@gmail.com
 */
class EnlaceVerificacion extends Command
{
    protected $signature = 'habbi:enlace
                            {correo : El correo del usuario}
                            {--verificar : En vez del enlace, marcarlo como verificado ya}';

    protected $description = 'Da el enlace de verificación de un usuario (o lo verifica directamente)';

    public function handle(): int
    {
        $correo = $this->argument('correo');

        $usuario = User::where('email', $correo)->first();

        if (! $usuario) {
            $this->error("No hay ningún usuario con el correo {$correo}.");

            $parecidos = User::where('email', 'like', '%' . explode('@', $correo)[0] . '%')
                ->limit(5)
                ->pluck('email');

            if ($parecidos->isNotEmpty()) {
                $this->line('');
                $this->line('¿Querías alguno de estos?');
                $parecidos->each(fn ($c) => $this->line("  · {$c}"));
            }

            return self::FAILURE;
        }

        if ($usuario->hasVerifiedEmail()) {
            $this->info("El correo de {$usuario->name} ya está verificado.");

            return self::SUCCESS;
        }

        if ($this->option('verificar')) {
            $usuario->markEmailAsVerified();
            $this->info("Listo: {$usuario->name} queda con el correo verificado.");

            return self::SUCCESS;
        }

        // Mismo enlace firmado que iría en el correo: mismo nombre de ruta,
        // misma caducidad y mismo hash. Si esto funciona, el correo también.
        $enlace = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id'   => $usuario->getKey(),
                'hash' => sha1($usuario->getEmailForVerification()),
            ]
        );

        $this->line('');
        $this->line("  Usuario:  {$usuario->name} <{$usuario->email}>");
        $this->line('  Caduca:   en ' . config('auth.verification.expire', 60) . ' minutos');
        $this->line('');
        $this->line('  Abre esto en el navegador:');
        $this->line('');
        $this->line("  {$enlace}");
        $this->line('');

        return self::SUCCESS;
    }
}
