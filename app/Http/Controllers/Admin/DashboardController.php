<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alojamiento;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /** Resumen general de la plataforma. */
    public function index(): View
    {
        return view('admin.index', [
            'totalUsuarios'     => User::count(),
            'totalAdmins'       => User::where('role', User::ROLE_ADMIN)->count(),
            'totalAlojamientos' => Alojamiento::count(),
            'totalActivos'      => Alojamiento::activos()->count(),
            'totalResenas'      => Rating::count(),
            'promedioGeneral'   => round((float) Rating::avg('rating'), 1),
            'ultimos'           => Alojamiento::with('user:id,name')->latest()->take(5)->get(),
            'ultimosUsuarios'   => User::latest()->take(5)->get(),
        ]);
    }
}
