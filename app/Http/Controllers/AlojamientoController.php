<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlojamientoRequest;
use App\Models\Alojamiento;
use App\Services\AlojamientoPhotoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlojamientoController extends Controller
{
    public function __construct(private readonly AlojamientoPhotoService $fotos)
    {
        // Solo el listado y el detalle son públicos.
        $this->middleware('auth')->except(['index', 'show']);
    }

    /** Buscador público con filtros, orden y paginación. */
    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'type', 'guests', 'price_min', 'price_max', 'amenities', 'sort']);

        $alojamientos = Alojamiento::query()
            ->activos()
            ->conResenas()
            ->filtrar($filtros)
            ->paginate(12)
            ->withQueryString();

        return view('alojamientos.index', compact('alojamientos', 'filtros'));
    }

    /** Formulario de publicación. */
    public function create(): View
    {
        return view('alojamientos.create');
    }

    /** Guarda una publicación nueva. */
    public function store(AlojamientoRequest $request): RedirectResponse
    {
        $datos  = $request->validated();
        $rutas  = $this->fotos->guardar($request->file('photos', []));

        $alojamiento = $request->user()->alojamientos()->create([
            ...$datos,
            'amenities'  => $datos['amenities'] ?? [],
            'photos'     => $rutas,
            'cover_path' => $rutas[0] ?? null,
        ]);

        return redirect()
            ->route('alojamientos.show', $alojamiento)
            ->with('success', '¡Tu alojamiento ya está publicado!');
    }

    /** Ficha pública del alojamiento. */
    public function show(Alojamiento $alojamiento): View
    {
        abort_unless(auth()->user()?->can('view', $alojamiento) ?? $alojamiento->is_active, 404);

        $alojamiento->load('user')->loadAvg('ratings', 'rating')->loadCount('ratings');

        $resenas = $alojamiento->ratings()
            ->with('user:id,name')
            ->latest()
            ->paginate(5);

        $miResena = auth()->check()
            ? $alojamiento->ratings()->where('user_id', auth()->id())->first()
            : null;

        return view('alojamientos.show', [
            'alojamiento' => $alojamiento,
            'galeria'     => $alojamiento->galeria(),
            'resenas'     => $resenas,
            'miResena'    => $miResena,
            'puedeVotar'  => auth()->check() && auth()->user()->can('rate', $alojamiento),
        ]);
    }

    /** Publicaciones del usuario autenticado. */
    public function mine(Request $request): View
    {
        $alojamientos = $request->user()
            ->alojamientos()
            ->conResenas()
            ->latest()
            ->paginate(9);

        return view('alojamientos.mine', compact('alojamientos'));
    }

    /** Formulario de edición. */
    public function edit(Alojamiento $alojamiento): View
    {
        $this->authorize('update', $alojamiento);

        return view('alojamientos.edit', [
            'alojamiento' => $alojamiento,
            'galeria'     => $alojamiento->galeria(),
        ]);
    }

    /** Actualiza la publicación y sincroniza la galería. */
    public function update(AlojamientoRequest $request, Alojamiento $alojamiento): RedirectResponse
    {
        $this->authorize('update', $alojamiento);

        $datos = $request->safe()->except(['new_photos', 'delete_photos', 'cover_path']);

        $alojamiento->update([
            ...$datos,
            'amenities' => $datos['amenities'] ?? [],
        ]);

        $this->fotos->sincronizar(
            alojamiento: $alojamiento,
            nuevas:      $request->file('new_photos', []),
            aEliminar:   $request->input('delete_photos', []),
            portada:     $request->input('cover_path'),
        );

        return redirect()
            ->route('alojamientos.show', $alojamiento)
            ->with('success', 'Alojamiento actualizado correctamente.');
    }

    /** Publica o despublica sin borrar (interruptor rápido desde "Mis alojamientos"). */
    public function toggle(Alojamiento $alojamiento): RedirectResponse
    {
        $this->authorize('update', $alojamiento);

        $alojamiento->update(['is_active' => !$alojamiento->is_active]);

        return back()->with('success', $alojamiento->is_active
            ? 'El alojamiento vuelve a estar visible.'
            : 'El alojamiento ya no aparece en las búsquedas.');
    }

    /** Elimina la publicación y sus fotos del disco. */
    public function destroy(Alojamiento $alojamiento): RedirectResponse
    {
        $this->authorize('delete', $alojamiento);

        $this->fotos->eliminarTodas($alojamiento);
        $alojamiento->forceDelete();

        return redirect()
            ->route('alojamientos.mine')
            ->with('success', 'Alojamiento eliminado.');
    }
}
