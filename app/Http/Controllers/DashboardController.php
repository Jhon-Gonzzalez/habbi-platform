<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Panel personal del usuario: resumen de sus publicaciones y reseñas. */
    public function index(Request $request): View
    {
        $usuario = $request->user();

        $alojamientos = $usuario->alojamientos()->conResenas()->latest()->take(4)->get();

        return view('dashboard', [
            'usuario'       => $usuario,
            'alojamientos'  => $alojamientos,
            'totalActivos'  => $usuario->alojamientos()->activos()->count(),
            'totalPausados' => $usuario->alojamientos()->where('is_active', false)->count(),
            'totalResenas'  => $usuario->ratings()->count(),
            'misResenas'    => $usuario->ratings()->with('alojamiento:id,title,cover_path')->latest()->take(5)->get(),
        ]);
    }
}
