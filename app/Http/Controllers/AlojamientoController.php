<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlojamientoRequest;
use App\Models\Alojamiento;
use Illuminate\Support\Facades\Gate;
use App\Services\AlojamientoPhotoService;
use App\Services\RevisionAlojamientoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlojamientoController extends Controller
{
    public function __construct(
        private readonly AlojamientoPhotoService $fotos,
        private readonly RevisionAlojamientoService $revision,
    ) {
        // Solo el listado y el detalle son públicos.
        $this->middleware('auth')->except(['index', 'show']);
    }

    /** Buscador público con filtros, orden y paginación. */
    public function index(Request $request): View
    {
        $filtros = $request->only([
            'q', 'type', 'guests', 'price_min', 'price_max', 'amenities', 'sort',
            'universidad', 'km',
        ]);

        $alojamientos = Alojamiento::query()
            ->disponibles()
            ->conResenas()
            ->filtrar($filtros)
            ->paginate(12)
            ->withQueryString();

        // Los pines de la página que se está viendo. No se pintan los de
        // toda la búsqueda: un mapa con cientos de puntos tarda, y el
        // mapa acompaña al listado, no lo sustituye.
        $puntos = $alojamientos->getCollection()
            ->map(fn (Alojamiento $a) => $a->puntoMapa())
            ->filter()
            ->values();

        return view('alojamientos.index', compact('alojamientos', 'filtros', 'puntos'));
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

        // Nace pendiente: no se ve hasta que un administrador la apruebe.
        // El valor por defecto de la columna ya es 'pendiente'; se pone
        // explícito para que leyendo esto se sepa sin ir a la migración.
        $alojamiento = $request->user()->alojamientos()->create([
            ...$datos,
            'amenities'  => $datos['amenities'] ?? [],
            'photos'     => $rutas,
            'cover_path' => $rutas[0] ?? null,
            'estado'     => Alojamiento::PENDIENTE,
        ]);

        return redirect()
            ->route('alojamientos.show', $alojamiento)
            ->with('success', 'Tu alojamiento quedó enviado a revisión. '
                . 'Un administrador lo aprobará y entonces aparecerá en el buscador. '
                . 'Mientras tanto puedes verlo y corregirlo desde aquí.');
    }

    /** Ficha pública del alojamiento. */
    public function show(Alojamiento $alojamiento): View
    {
        /*
         * Se pregunta a la Gate y no a auth()->user()?->can().
         *
         * Con el operador nulo, un visitante SIN SESIÓN no pasaba por la
         * policy en absoluto: caía al valor por defecto, que solo miraba
         * is_active, y así veía publicaciones pendientes de revisión. La
         * policy acepta ?User justamente para cubrir a los invitados.
         */
        abort_unless(Gate::allows('view', $alojamiento), 404);

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

        // Si estaba rechazada, vuelve a la cola; si estaba aprobada, se queda
        // aprobada y la edición se anota en la auditoría. El porqué está en
        // RevisionAlojamientoService.
        $vuelveALaCola = $this->revision->alEditar($alojamiento, $request->user());

        $this->fotos->sincronizar(
            alojamiento: $alojamiento,
            nuevas:      $request->file('new_photos', []),
            aEliminar:   $request->input('delete_photos', []),
            portada:     $request->input('cover_path'),
        );

        return redirect()
            ->route('alojamientos.show', $alojamiento)
            ->with('success', $vuelveALaCola
                ? 'Cambios guardados. Como la publicación estaba rechazada, vuelve a revisión.'
                : 'Alojamiento actualizado correctamente.');
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
