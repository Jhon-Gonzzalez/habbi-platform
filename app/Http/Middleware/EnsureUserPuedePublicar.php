<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Publicar exige ser arrendador verificado. A quien no lo sea se le
 * redirige a la solicitud en lugar de darle un 403 a secas: es una
 * puerta que puede abrir, no una prohibición.
 */
class EnsureUserPuedePublicar
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->puedePublicar()) {
            return redirect()
                ->route('arrendador.solicitud')
                ->with('error', 'Para publicar necesitas verificar tu cuenta como arrendador.');
        }

        return $next($request);
    }
}
