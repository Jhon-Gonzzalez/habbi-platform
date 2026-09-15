<?php

namespace App\Http\Controllers;

use App\Models\Alojamiento;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /** Landing pública con los alojamientos mejor valorados. */
    public function index(): View
    {
        $destacados = Alojamiento::query()
            ->activos()
            ->conResenas()
            ->orderByDesc('ratings_avg_rating')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        return view('index', compact('destacados'));
    }
}
