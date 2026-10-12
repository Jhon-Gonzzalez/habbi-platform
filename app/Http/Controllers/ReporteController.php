<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReporteRequest;
use App\Models\Alojamiento;
use App\Models\Reporte;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Reportar una publicación que tiene algún problema. */
class ReporteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Request $request, Alojamiento $alojamiento): View
    {
        return view('reportes.create', [
            'alojamiento' => $alojamiento,
            'yaReportado' => $request->user()->reportes()
                ->where('alojamiento_id', $alojamiento->id)
                ->exists(),
        ]);
    }

    public function store(ReporteRequest $request, Alojamiento $alojamiento): RedirectResponse
    {
        // No se puede reportar lo propio: para eso está "editar" o "pausar".
        if ($alojamiento->user_id === $request->user()->id) {
            return back()->with('error', 'No puedes reportar tu propia publicación.');
        }

        $reporte = Reporte::firstOrNew([
            'user_id'        => $request->user()->id,
            'alojamiento_id' => $alojamiento->id,
        ]);

        if ($reporte->exists && $reporte->estaAbierto()) {
            return redirect()
                ->route('alojamientos.show', $alojamiento)
                ->with('success', 'Ya tenemos tu reporte y lo estamos revisando.');
        }

        // Si su reporte anterior ya se cerró, se reabre con los datos nuevos
        // en vez de crear otro: así el historial queda en una sola fila.
        $reporte->fill($request->validated());
        $reporte->estado       = Reporte::ESTADO_ABIERTO;
        $reporte->revisado_por = null;
        $reporte->revisado_en  = null;
        $reporte->save();

        return redirect()
            ->route('alojamientos.show', $alojamiento)
            ->with('success', 'Gracias por avisarnos. Un administrador va a revisar esta publicación.');
    }
}
