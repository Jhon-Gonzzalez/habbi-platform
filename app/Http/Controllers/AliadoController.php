<?php

namespace App\Http\Controllers;

use App\Models\Aliado;
use Illuminate\Http\RedirectResponse;

/**
 * La salida hacia el sitio del aliado.
 *
 * El enlace del anuncio pasa por aquí en vez de ir directo, para poder
 * contar cuántas personas hicieron clic. Ese número es lo único que
 * permite justificar una renovación con datos en vez de con promesas.
 *
 * Se cuenta el clic, no quién lo hizo: un contador en la fila del
 * aliado. Ni usuario, ni IP, ni cookie. El aliado sabe cuánta gente
 * entró; no sabe quién.
 */
class AliadoController extends Controller
{
    public function salir(Aliado $aliado): RedirectResponse
    {
        abort_unless($aliado->estaVigente(), 404);

        // increment() hace un UPDATE atómico: dos clics a la vez no se
        // pisan, cosa que leer-sumar-guardar sí haría.
        $aliado->increment('clics');

        return redirect()->away($aliado->url);
    }
}
