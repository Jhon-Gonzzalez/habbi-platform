<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Reporte;
use App\Services\Auditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Revisión de los reportes que mandan los estudiantes. */
class ReporteController extends Controller
{
    private const ESTADOS = [
        Reporte::ESTADO_ABIERTO    => 'Abiertos',
        Reporte::ESTADO_ATENDIDO   => 'Atendidos',
        Reporte::ESTADO_DESCARTADO => 'Descartados',
    ];

    public function index(Request $request): View
    {
        $estado = $request->input('estado', Reporte::ESTADO_ABIERTO);

        $reportes = Reporte::query()
            ->with(['user:id,name,email', 'alojamiento:id,title,user_id,is_active', 'alojamiento.user:id,name', 'revisor:id,name'])
            ->when(
                array_key_exists($estado, self::ESTADOS),
                fn ($sql) => $sql->where('estado', $estado),
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reportes.index', [
            'reportes' => $reportes,
            'estado'   => $estado,
            'estados'  => self::ESTADOS,
            'abiertos' => Reporte::abiertos()->count(),
        ]);
    }

    /**
     * Cierra el reporte. Lo que se hace con la publicación (pausarla o
     * borrarla) es una decisión aparte, en la pantalla de alojamientos: así
     * cerrar un reporte nunca tumba un anuncio por accidente.
     */
    public function resolver(Request $request, Reporte $reporte): RedirectResponse
    {
        $datos = $request->validate([
            'estado'       => ['required', 'in:' . Reporte::ESTADO_ATENDIDO . ',' . Reporte::ESTADO_DESCARTADO],
            'nota_interna' => ['nullable', 'string', 'max:1000'],
        ], [], ['nota_interna' => 'nota']);

        $reporte->update([
            'estado'       => $datos['estado'],
            'nota_interna' => $datos['nota_interna'] ?? null,
            'revisado_por' => $request->user()->id,
            'revisado_en'  => now(),
        ]);

        Auditor::registrar(
            Auditoria::REPORTE_RESUELTO,
            ($datos['estado'] === Reporte::ESTADO_ATENDIDO ? 'Atendió' : 'Descartó')
                . ' el reporte sobre «' . ($reporte->alojamiento->title ?? 'un alojamiento eliminado') . '»',
            $reporte,
            [
                'motivo' => $reporte->motivo,
                'estado' => $datos['estado'],
                'nota'   => $datos['nota_interna'] ?? null,
            ],
        );

        return back()->with('success', 'Reporte cerrado.');
    }
}
