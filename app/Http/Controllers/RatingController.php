<?php

namespace App\Http\Controllers;

use App\Http\Requests\RatingRequest;
use App\Models\Alojamiento;
use App\Models\Auditoria;
use App\Models\Rating;
use App\Services\Auditor;
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

        // Solo se audita cuando un administrador borra la reseña de OTRO.
        // Que alguien borre la suya es un acto normal, no un ejercicio de
        // poder, y llenar el registro de eso lo volvería ilegible.
        $esDeOtro = $rating->user_id !== auth()->id();

        if ($esDeOtro) {
            Auditor::registrar(
                Auditoria::RESENA_BORRADA,
                'Borró la reseña de ' . ($rating->user->name ?? 'un usuario eliminado')
                    . ' sobre «' . ($rating->alojamiento->title ?? 'un alojamiento eliminado') . '»',
                $rating,
                [
                    'puntuacion' => $rating->rating,
                    'comentario' => $rating->comment,
                    'autor_id'   => $rating->user_id,
                ],
            );
        }

        $rating->delete();

        return back()->with('success', $esDeOtro
            ? 'Reseña eliminada. La acción quedó registrada en la auditoría.'
            : 'Tu reseña fue eliminada.');
    }
}
