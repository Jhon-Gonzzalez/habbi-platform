<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alojamiento;
use App\Services\RevisionAlojamientoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Revisión de las publicaciones antes de que se vean (RF-10, CU-07). */
class RevisionController extends Controller
{
    public function __construct(private readonly RevisionAlojamientoService $revision)
    {
    }

    public function index(Request $request): View
    {
        $estado = $request->input('estado', Alojamiento::PENDIENTE);

        $alojamientos = Alojamiento::query()
            ->with(['user:id,name,email,verificado_en'])
            ->when(
                array_key_exists($estado, Alojamiento::ESTADOS),
                fn ($sql) => $sql->where('estado', $estado),
            )
            ->oldest('created_at')   // lo que más lleva esperando, primero
            ->paginate(15)
            ->withQueryString();

        return view('admin.revision.index', [
            'alojamientos' => $alojamientos,
            'estado'       => $estado,
            'pendientes'   => Alojamiento::pendientes()->count(),
        ]);
    }

    public function show(Alojamiento $alojamiento): View
    {
        return view('admin.revision.show', [
            'alojamiento' => $alojamiento->load(['user', 'revisor:id,name']),
        ]);
    }

    public function aprobar(Request $request, Alojamiento $alojamiento): RedirectResponse
    {
        $this->revision->aprobar($alojamiento, $request->user());

        return redirect()
            ->route('admin.revision.index')
            ->with('success', "«{$alojamiento->title}» ya es visible en el buscador.");
    }

    public function rechazar(Request $request, Alojamiento $alojamiento): RedirectResponse
    {
        $datos = $request->validate(
            ['motivo' => ['required', 'string', 'min:15', 'max:500']],
            ['motivo.min' => 'Explica el motivo con algo de detalle: el arrendador va a leerlo y '
                . 'tiene que saber qué corregir.'],
            ['motivo' => 'motivo del rechazo'],
        );

        $this->revision->rechazar($alojamiento, $request->user(), $datos['motivo']);

        return redirect()
            ->route('admin.revision.index')
            ->with('success', 'Publicación rechazada. El arrendador verá el motivo y podrá corregirla.');
    }
}
