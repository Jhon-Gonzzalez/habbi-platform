<?php

namespace App\Http\Controllers;

use App\Models\Alojamiento;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Guardar alojamientos para volver a verlos después. */
class FavoritoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $favoritos = $request->user()
            ->favoritos()
            ->activos()
            ->with('user')
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->paginate(12);

        return view('favoritos.index', compact('favoritos'));
    }

    /**
     * Guarda o quita, según cómo esté. Un solo botón para las dos cosas
     * evita que la interfaz se quede desincronizada con la base.
     */
    public function toggle(Request $request, Alojamiento $alojamiento): RedirectResponse
    {
        $resultado = $request->user()->favoritos()->toggle($alojamiento);

        $guardado = ! empty($resultado['attached']);

        return back()->with('success', $guardado
            ? 'Guardado en tus favoritos.'
            : 'Quitado de tus favoritos.');
    }
}
