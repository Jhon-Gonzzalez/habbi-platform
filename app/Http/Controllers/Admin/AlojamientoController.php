<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alojamiento;
use App\Services\AlojamientoPhotoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Moderación de publicaciones. */
class AlojamientoController extends Controller
{
    public function __construct(private readonly AlojamientoPhotoService $fotos)
    {
    }

    public function index(Request $request): View
    {
        $alojamientos = Alojamiento::query()
            ->with('user:id,name,email')
            ->conResenas()
            ->when($request->input('q'), function ($sql, string $q) {
                $term = '%' . addcslashes($q, '%_\\') . '%';
                $sql->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('city', 'like', $term));
            })
            ->when($request->input('estado') === 'pausados', fn ($sql) => $sql->where('is_active', false))
            ->when($request->input('estado') === 'activos', fn ($sql) => $sql->where('is_active', true))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.alojamientos.index', compact('alojamientos'));
    }

    /** Publica o despublica cualquier alojamiento. */
    public function toggle(Alojamiento $alojamiento): RedirectResponse
    {
        $alojamiento->update(['is_active' => !$alojamiento->is_active]);

        return back()->with('success', $alojamiento->is_active
            ? "«{$alojamiento->title}» vuelve a estar visible."
            : "«{$alojamiento->title}» fue despublicado.");
    }

    public function destroy(Alojamiento $alojamiento): RedirectResponse
    {
        $this->fotos->eliminarTodas($alojamiento);
        $alojamiento->forceDelete();

        return back()->with('success', 'Alojamiento eliminado de la plataforma.');
    }
}
