<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReporteEstadisticas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Informes de gestión (RF-11, CU-08).
 *
 * Se llama «informe» y no «reporte» a propósito: en esta plataforma un
 * reporte es lo que manda un estudiante cuando ve algo raro en un anuncio.
 * Mezclar los dos nombres en el panel confundiría a quien lo administra.
 */
class InformeController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.informes.index', [
            'datos' => $this->datos($request),
        ]);
    }

    public function pdf(Request $request): Response
    {
        $datos = $this->datos($request);

        /*
         * El PDF usa el MISMO parcial que la pantalla.
         *
         * dompdf no ejecuta JavaScript, así que las gráficas están hechas
         * con divs y anchos en porcentaje. Si se hubieran hecho con una
         * librería de JS, el PDF saldría con los huecos en blanco.
         */
        return Pdf::loadView('admin.informes.pdf', ['datos' => $datos])
            ->setPaper('a4')
            ->download($datos->nombreArchivo());
    }

    private function datos(Request $request): ReporteEstadisticas
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ], [], ['desde' => 'fecha inicial', 'hasta' => 'fecha final']);

        return ReporteEstadisticas::entre($request->input('desde'), $request->input('hasta'));
    }
}
