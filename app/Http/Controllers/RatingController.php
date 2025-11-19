<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Alojamiento;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        // Validar datos del formulario
        $data = $request->validate([
            'alojamiento_id' => 'required|exists:alojamientos,id',
            'rating'         => 'required|integer|min:1|max:5',
            'comment'        => 'nullable|string|max:1000',
        ]);

        // Obtener alojamiento
        $alojamiento = Alojamiento::find($request->alojamiento_id);

        // Guardar o actualizar calificación
        Rating::updateOrCreate(
            [
                'user_id'        => auth()->id(),
                'alojamiento_id' => $alojamiento->id
            ],
            [
                'rating'  => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        return back()->with('success', 'Gracias por tu calificación.');
    }
}
