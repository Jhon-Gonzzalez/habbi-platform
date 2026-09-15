<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Restringe el acceso al panel de administración. */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->isAdmin()) {
            abort(403, 'Necesitas permisos de administrador para entrar aquí.');
        }

        return $next($request);
    }
}
