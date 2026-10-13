<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;

/**
 * Exige correo verificado SOLO si la verificación está encendida.
 *
 * Reemplaza al alias «verified» de Laravel. Se hace así, y no quitando el
 * middleware de las rutas, para que encender y apagar la verificación sea
 * un cambio de una línea en el .env y no una edición de routes/web.php que
 * haya que recordar deshacer.
 *
 * Cuando está encendida, el comportamiento es exactamente el de Laravel:
 * se delega en su middleware, sin reimplementar la lógica.
 */
class VerificarCorreo extends EnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (! config('habbi.verificar_correo')) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
