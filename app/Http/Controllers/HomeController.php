<?php

namespace App\Http\Controllers;

use App\Models\Alojamiento;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Portada pública con los alojamientos mejor calificados.
     *
     * No se llaman «destacados» aunque lo parezca: desde que existen las
     * campañas de pago, esa palabra es la etiqueta de un anuncio pagado.
     * Estos salen por sus reseñas, y aquí no se puede pagar por entrar.
     */
    public function index(): View
    {
        $mejorCalificados = Alojamiento::query()
            ->disponibles()
            ->conResenas()
            ->orderByDesc('ratings_avg_rating')
            ->orderByDesc('created_at')
            // Cuatro, que es lo que cabe en una fila. Con seis la segunda
            // fila quedaba con dos tarjetas sueltas.
            ->take(4)
            ->get();

        return view('index', compact('mejorCalificados'));
    }
}
