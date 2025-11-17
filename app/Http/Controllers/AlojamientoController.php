<?php

namespace App\Http\Controllers;

use App\Models\Alojamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlojamientoController extends Controller
{
    public function __construct()
    {
        // Hacemos públicas estas acciones; el resto requiere auth
        $this->middleware('auth')->except(['index','create','store','show']);
    }

    /** Listado con filtros y orden */
    public function index(Request $request)
    {
        $q         = $request->input('q');
        $type      = $request->input('type');
        $guests    = $request->input('guests');
        $priceMin  = $request->input('price_min');
        $priceMax  = $request->input('price_max');
        $amenities = $request->input('amenities', []);
        $sort      = $request->input('sort');

        $alojamientos = Alojamiento::query()
            ->when($q, fn($sql) =>
                $sql->where(fn($w) =>
                    $w->where('title','like',"%$q%")
                      ->orWhere('city','like',"%$q%")
                      ->orWhere('neighborhood','like',"%$q%")
                )
            )
            ->when($type, fn($sql) => $sql->where('type', $type))
            ->when($guests, fn($sql) => $sql->where('guests','>=', (int)$guests))
            ->when($priceMin, fn($sql) => $sql->where('price','>=',(int)$priceMin))
            ->when($priceMax, fn($sql) => $sql->where('price','<=',(int)$priceMax))
            ->when(!empty($amenities), function ($sql) use ($amenities) {
                foreach ($amenities as $a) {
                    $sql->whereJsonContains('amenities', $a);
                }
            })
            ->when($sort, function ($sql) use ($sort) {
                match ($sort) {
                    'price_asc'  => $sql->orderBy('price', 'asc'),
                    'price_desc' => $sql->orderBy('price', 'desc'),
                    'recent'     => $sql->latest(),
                    'oldest'     => $sql->oldest(),
                    default      => $sql->latest(),
                };
            }, fn($sql) => $sql->latest())
            ->paginate(12)
            ->withQueryString();

        return view('Alojamiento.alojamientos', compact('alojamientos'));
    }

    /** Form crear */
    public function create()
    {
        return view('Alojamiento.publicar');
    }

    /** Guardar nuevo */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'type'          => 'required|string|max:50',
            'price'         => 'required|integer|min:0',
            'price_period'  => 'required|in:mes,noche',
            'guests'        => 'required|integer|min:1|max:8',
            'city'          => 'required|string|max:120',
            'neighborhood'  => 'nullable|string|max:120',
            'address'       => 'nullable|string|max:255',
            'description'   => 'required|string',
            'amenities'     => 'nullable|array',
            'amenities.*'   => 'string|max:50',
            'phone'         => 'nullable|string|max:40',
            'photos'        => 'nullable|array|max:8',
            'photos.*'      => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $paths = [];

        if ($request->hasFile('photos')) {
            $files = $request->file('photos');
            if (!is_array($files)) { $files = [$files]; }

            // Guarda imágenes válidas
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $paths[] = $file->store('alojamientos', 'public');
                }
            }
        }

        Alojamiento::create([
            'user_id'      => auth()->id(),
            'title'        => $data['title'],
            'type'         => $data['type'],
            'price'        => $data['price'],
            'price_period' => $data['price_period'],
            'guests'       => $data['guests'],
            'city'         => $data['city'],
            'neighborhood' => $data['neighborhood'] ?? null,
            'address'      => $data['address'] ?? null,
            'description'  => $data['description'],
            'amenities'    => $data['amenities'] ?? [],
            'phone'        => $data['phone'] ?? null,
            'photos'       => $paths,
            'cover_path'   => $paths[0] ?? null,
        ]);

        return redirect()->route('alojamiento.index')
                         ->with('success', 'Alojamiento publicado correctamente.');
    }

    /** Detalle */
    public function show(Alojamiento $alojamiento)
    {
        $photos = collect($alojamiento->photos ?? []);

        if ($alojamiento->cover_path && !$photos->contains($alojamiento->cover_path)) {
            $photos->prepend($alojamiento->cover_path);
        }

        $photoUrls = $photos->unique()
            ->filter(fn($p) => Storage::disk('public')->exists($p))
            ->map(fn($p) => asset('storage/' . $p))
            ->values()
            ->all();

        if (empty($photoUrls)) {
            $photoUrls = [asset('assets/img/images/no-image.png')];
        }

        return view('Alojamiento.show', [
            'a'         => $alojamiento,
            'photoUrls' => $photoUrls,
        ]);
    }

    /** Mis alojamientos (solo del usuario logueado) */
    public function mine()
    {
        $alojamientos = Alojamiento::where('user_id', auth()->id())
                        ->latest()->paginate(12);

        return view('Alojamiento.mine', compact('alojamientos'));
    }

    /** Form editar */
    public function edit(Alojamiento $alojamiento)
    {
        $this->authorize('update', $alojamiento);
        return view('Alojamiento.edit', ['a' => $alojamiento]);
    }

    /** Actualizar */
    public function update(Request $request, Alojamiento $alojamiento)
    {
        $this->authorize('update', $alojamiento);

        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'type'          => 'required|string|max:50',
            'price'         => 'required|integer|min:0',
            'price_period'  => 'required|in:mes,noche',
            'guests'        => 'required|integer|min:1|max:8',
            'city'          => 'required|string|max:120',
            'neighborhood'  => 'nullable|string|max:120',
            'address'       => 'nullable|string|max:255',
            'description'   => 'required|string',
            'amenities'     => 'nullable|array',
            'amenities.*'   => 'string|max:50',
            'phone'         => 'nullable|string|max:40',
            'new_photos'    => 'nullable|array|max:8',
            'new_photos.*'  => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'cover_index'   => 'nullable|integer',
        ]);

        $photos = collect($alojamiento->photos ?? []);

        // Agregar nuevas fotos si llegan
        if ($request->hasFile('new_photos')) {
            foreach ($request->file('new_photos') as $file) {
                if ($file && $file->isValid()) {
                    $photos->push($file->store('alojamientos', 'public'));
                }
            }
        }

        // Resolver portada
        $coverPath = $alojamiento->cover_path;
        if ($request->filled('cover_index') && isset($photos[$request->cover_index])) {
            $coverPath = $photos[$request->cover_index];
        } elseif (!$coverPath && $photos->count()) {
            $coverPath = $photos->first();
        }

        // Actualizar
        $alojamiento->update([
            'title'        => $data['title'],
            'type'         => $data['type'],
            'price'        => $data['price'],
            'price_period' => $data['price_period'],
            'guests'       => $data['guests'],
            'city'         => $data['city'],
            'neighborhood' => $data['neighborhood'] ?? null,
            'address'      => $data['address'] ?? null,
            'description'  => $data['description'],
            'amenities'    => $data['amenities'] ?? [],
            'phone'        => $data['phone'] ?? null,
            'photos'       => $photos->values()->all(),
            'cover_path'   => $coverPath,
        ]);

        return redirect()->route('alojamiento.mine')->with('success','Alojamiento actualizado.');
    }

    /** Eliminar */
    public function destroy(Alojamiento $alojamiento)
    {
        $this->authorize('delete', $alojamiento);
        $alojamiento->delete();
        return back()->with('success','Alojamiento eliminado.');
    }
}
