<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alojamiento;
use App\Models\Auditoria;
use App\Services\AlojamientoPhotoService;
use App\Services\Auditor;
use App\Services\RevisionAlojamientoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Las publicaciones, en una sola pantalla.
 *
 * Antes había dos, y listaban lo mismo:
 *
 *   «Revisión»     → aprobar o rechazar, filtrando por estado
 *   «Alojamientos» → pausar o eliminar, filtrando por activo/pausado
 *
 * Un administrador veía la misma publicación en dos sitios del menú,
 * con la mitad de las acciones en cada uno, y tenía que acordarse de en
 * cuál estaba lo que quería hacer. Aquí están todas juntas y cada una
 * aparece cuando tiene sentido: aprobar y rechazar en las pendientes,
 * pausar y eliminar en las que ya se ven.
 */
class PublicacionController extends Controller
{
    public function __construct(
        private readonly RevisionAlojamientoService $revision,
        private readonly AlojamientoPhotoService $fotos,
    ) {
    }

    public function index(Request $request): View
    {
        $estado = $request->input('estado', Alojamiento::PENDIENTE);

        $alojamientos = Alojamiento::query()
            ->with(['user:id,name,email,verificado_en'])
            ->when($request->input('q'), function ($sql, string $q) {
                $termino = '%' . addcslashes($q, '%_\\') . '%';

                $sql->where(fn ($w) => $w
                    ->where('title', 'like', $termino)
                    ->orWhere('city', 'like', $termino)
                    ->orWhere('neighborhood', 'like', $termino));
            })
            ->when(
                array_key_exists($estado, Alojamiento::ESTADOS),
                fn ($sql) => $sql->where('estado', $estado),
            )
            // «Pausadas» no es un estado de revisión sino un interruptor
            // del arrendador, pero para quien modera es una pestaña más:
            // son las que están aprobadas y aun así no se ven.
            ->when($estado === 'pausadas', fn ($sql) => $sql
                ->where('estado', Alojamiento::APROBADA)
                ->where('is_active', false))
            // Lo que más lleva esperando, primero: es una cola.
            ->oldest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.publicaciones.index', [
            'alojamientos' => $alojamientos,
            'estado'       => $estado,
            'pendientes'   => Alojamiento::pendientes()->count(),
            'pausadas'     => Alojamiento::where('estado', Alojamiento::APROBADA)
                ->where('is_active', false)->count(),
        ]);
    }

    public function show(Alojamiento $alojamiento): View
    {
        return view('admin.publicaciones.show', [
            'alojamiento' => $alojamiento->load(['user', 'revisor:id,name']),
            'galeria'     => $alojamiento->galeria(),
        ]);
    }

    public function aprobar(Request $request, Alojamiento $alojamiento): RedirectResponse
    {
        $this->revision->aprobar($alojamiento, $request->user());

        return redirect()
            ->route('admin.publicaciones.index')
            ->with('success', "«{$alojamiento->title}» ya es visible en el buscador.");
    }

    public function rechazar(Request $request, Alojamiento $alojamiento): RedirectResponse
    {
        $datos = $request->validate([
            // Mínimo 15 caracteres: el arrendador va a leer esto y tiene
            // que poder corregir. «No sirve» no le dice nada.
            'motivo' => ['required', 'string', 'min:15', 'max:500'],
        ], [], ['motivo' => 'motivo del rechazo']);

        $this->revision->rechazar($alojamiento, $request->user(), $datos['motivo']);

        return redirect()
            ->route('admin.publicaciones.index')
            ->with('success', 'Publicación rechazada. El arrendador verá el motivo y podrá corregirla.');
    }

    /** Pausa o vuelve a publicar, sin tocar el estado de revisión. */
    public function toggle(Alojamiento $alojamiento): RedirectResponse
    {
        $alojamiento->update(['is_active' => ! $alojamiento->is_active]);

        Auditor::registrar(
            $alojamiento->is_active ? Auditoria::ALOJAMIENTO_ACTIVADO : Auditoria::ALOJAMIENTO_PAUSADO,
            ($alojamiento->is_active ? 'Volvió a publicar' : 'Despublicó')
                . ' «' . $alojamiento->title . '», de ' . ($alojamiento->user->name ?? 'un usuario eliminado'),
            $alojamiento,
            ['dueno_id' => $alojamiento->user_id],
        );

        return back()->with('success', $alojamiento->is_active
            ? "«{$alojamiento->title}» vuelve a estar visible."
            : "«{$alojamiento->title}» fue despublicado.");
    }

    public function destroy(Alojamiento $alojamiento): RedirectResponse
    {
        // Se audita ANTES de borrar: después ya no hay de dónde sacar el
        // título ni el dueño.
        Auditor::registrar(
            Auditoria::ALOJAMIENTO_BORRADO,
            'Eliminó «' . $alojamiento->title . '», de ' . ($alojamiento->user->name ?? 'un usuario eliminado'),
            $alojamiento,
            [
                'dueno_id' => $alojamiento->user_id,
                'ciudad'   => $alojamiento->city,
                'precio'   => $alojamiento->price,
                'resenas'  => $alojamiento->ratings()->count(),
            ],
        );

        $this->fotos->eliminarTodas($alojamiento);
        $alojamiento->forceDelete();

        return back()->with('success', 'Publicación eliminada.');
    }
}
