<?php

namespace App\Http\Controllers;

use App\Models\Alojamiento;
use Illuminate\Contracts\View\View;

/**
 * La página donde el arrendador ve qué es destacar y cómo pedirlo.
 *
 * No cobra nada: explica los planes y da el contacto. El pago ocurre por
 * Nequi o Daviplata y lo registra un administrador. Es un paso manual a
 * propósito mientras el volumen no justifique una pasarela.
 */
class DestacarController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Alojamiento $alojamiento): View
    {
        $this->authorize('update', $alojamiento);

        return view('alojamientos.destacar', [
            'alojamiento' => $alojamiento,
            'planes'      => config('destacados.planes'),
            'contacto'    => config('destacados.contacto'),
        ]);
    }
}
