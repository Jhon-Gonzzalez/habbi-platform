<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\SolicitudArrendador;
use App\Models\User;
use App\Services\Auditor;
use App\Services\VerificacionArrendadorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Revisión de las solicitudes para ser arrendador. */
class SolicitudController extends Controller
{
    public function __construct(private readonly VerificacionArrendadorService $verificacion)
    {
    }

    public function index(Request $request): View
    {
        $estado = $request->input('estado', SolicitudArrendador::PENDIENTE);

        $solicitudes = SolicitudArrendador::query()
            ->with(['user:id,name,email,created_at', 'revisor:id,name'])
            ->when(
                in_array($estado, array_keys(SolicitudArrendador::ESTADOS), true),
                fn ($sql) => $sql->where('estado', $estado),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.solicitudes.index', [
            'solicitudes'      => $solicitudes,
            'estado'           => $estado,
            'totalPendientes'  => SolicitudArrendador::pendientes()->count(),
        ]);
    }

    public function show(SolicitudArrendador $solicitud): View
    {
        $solicitud->load(['user', 'revisor:id,name']);

        return view('admin.solicitudes.show', compact('solicitud'));
    }

    public function aprobar(Request $request, SolicitudArrendador $solicitud): RedirectResponse
    {
        $this->authorize('revisar', $solicitud);

        $this->verificacion->aprobar($solicitud, $request->user());

        Auditor::registrar(
            Auditoria::ARRENDADOR_APROBADO,
            'Aprobó a ' . $solicitud->user->name . ' como arrendador verificado',
            $solicitud->user,
            ['solicitud_id' => $solicitud->id],
        );

        return redirect()
            ->route('admin.solicitudes.index')
            ->with('success', "{$solicitud->user->name} ya puede publicar y aparece como verificado.");
    }

    public function rechazar(Request $request, SolicitudArrendador $solicitud): RedirectResponse
    {
        $this->authorize('revisar', $solicitud);

        $datos = $request->validate(
            ['motivo' => ['required', 'string', 'min:15', 'max:500']],
            ['motivo.min' => 'Explica el motivo con algo de detalle: la persona va a leerlo.'],
            ['motivo' => 'motivo del rechazo'],
        );

        $this->verificacion->rechazar($solicitud, $request->user(), $datos['motivo']);

        Auditor::registrar(
            Auditoria::ARRENDADOR_RECHAZADO,
            'Rechazó la solicitud de ' . $solicitud->user->name,
            $solicitud->user,
            ['solicitud_id' => $solicitud->id, 'motivo' => $datos['motivo']],
        );

        return redirect()
            ->route('admin.solicitudes.index')
            ->with('success', 'Solicitud rechazada. El usuario puede corregir y volver a enviarla.');
    }

    /** Retira la condición de arrendador a un usuario ya aprobado. */
    public function revocar(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puedes revocarte a ti mismo.');
        }

        $this->verificacion->revocar($usuario);

        Auditor::registrar(
            Auditoria::ARRENDADOR_REVOCADO,
            'Retiró a ' . $usuario->name . ' la condición de arrendador',
            $usuario,
            ['alojamientos_que_tenia' => $usuario->alojamientos()->count()],
        );

        return back()->with('success', "{$usuario->name} ya no puede publicar. Sus alojamientos siguen ahí: revísalos aparte si hace falta.");
    }
}
