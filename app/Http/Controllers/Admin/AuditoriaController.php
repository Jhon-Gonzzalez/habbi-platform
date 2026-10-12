<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Consulta del registro de auditoría.
 *
 * Solo tiene index(): no hay borrar, no hay editar, y no es un olvido.
 * Un registro que el vigilado puede limpiar no vigila nada.
 */
class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        $registros = Auditoria::query()
            ->with('actor:id,name')
            ->deAccion($request->input('accion'))
            ->deActor($request->integer('actor') ?: null)
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.auditoria.index', [
            'registros' => $registros,
            'acciones'  => Auditoria::ETIQUETAS,
            'accion'    => $request->input('accion'),
            'actor'     => $request->integer('actor'),
            'actores'   => User::where('role', User::ROLE_ADMIN)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
