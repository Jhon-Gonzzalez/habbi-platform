<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Log;

/**
 * Manda el correo de verificación al registrarse, pero sin tumbar el
 * registro si el envío falla.
 *
 * Reemplaza al listener que trae Laravel. La diferencia está en el try:
 * con el de serie, un SMTP mal configurado o caído devuelve un error 500
 * JUSTO DESPUÉS de haber creado la cuenta, así que la persona ve una
 * pantalla de error aunque su usuario ya existe y no entiende si quedó
 * registrada o no. Aquí la cuenta queda creada, el fallo queda en el log
 * para quien administre, y el usuario puede pedir el enlace otra vez.
 */
class EnviarVerificacionDeCorreo
{
    public function handle(Registered $event): void
    {
        $usuario = $event->user;

        if (! $usuario instanceof MustVerifyEmail || $usuario->hasVerifiedEmail()) {
            return;
        }

        // Con la verificación apagada no se manda nada: la cuenta queda
        // verificada al instante. Así el proyecto funciona recién clonado,
        // sin montar un servidor de correo.
        if (! config('habbi.verificar_correo')) {
            $usuario->markEmailAsVerified();

            return;
        }

        try {
            $usuario->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el correo de verificación.', [
                'usuario' => $usuario->getKey(),
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
