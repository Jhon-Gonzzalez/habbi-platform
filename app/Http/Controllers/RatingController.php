<?php

namespace App\Http\Controllers;

use App\Http\Requests\RatingRequest;
use App\Models\Alojamiento;
use App\Models\Rating;
use Illuminate\Http\RedirectResponse;

class RatingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Crea o actualiza la reseña del usuario sobre un alojamiento. */
    public function store(RatingRequest $request, Alojamiento $alojamiento): RedirectResponse
    {
        $this->authorize('rate', $alojamiento);

        $alojamiento->ratings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated(),
        );

        return back()->with('success', 'Gracias por compartir tu experiencia.');
    }

    /** Elimina la reseña propia. */
    public function destroy(Rating $rating): RedirectResponse
    {
        $this->authorize('delete', $rating);

        $rating->delete();

        return back()->with('success', 'Tu reseña fue eliminada.');
    }
}
