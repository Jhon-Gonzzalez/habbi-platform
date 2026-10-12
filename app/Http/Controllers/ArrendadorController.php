<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudArrendadorRequest;
use App\Models\SolicitudArrendador;
use App\Models\User;
use App\Services\VerificacionArrendadorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArrendadorController extends Controller
{
    public function __construct(private readonly VerificacionArrendadorService $verificacion)
    {
        $this->middleware('auth')->except(['perfil']);
    }

    /** Perfil público de un arrendador. */
    public function perfil(User $usuario): View
    {
        abort_unless($usuario->esArrendador() || $usuario->isAdmin(), 404);

        return view('arrendadores.perfil', [
            'arrendador'   => $usuario,
            'alojamientos' => $usuario->alojamientos()->activos()->conResenas()->latest()->get(),
            'promedio'     => $usuario->promedioComoArrendador(),
            'totalResenas' => $usuario->totalResenasRecibidas(),
        ]);
    }

    /** Formulario de solicitud, o el estado de la que ya enviaste. */
    public function solicitud(Request $request): View
    {
        $usuario = $request->user();

        return view('arrendador.solicitud', [
            'usuario'   => $usuario,
            'solicitud' => $usuario->ultimaSolicitud()->with('revisor:id,name')->first(),
            'puedePedir' => $usuario->can('crear', SolicitudArrendador::class),
        ]);
    }

    /** Envía la solicitud con los documentos. */
    public function enviarSolicitud(SolicitudArrendadorRequest $request): RedirectResponse
    {
        $this->verificacion->solicitar(
            usuario:   $request->user(),
            documento: $request->file('documento'),
            prueba:    $request->file('prueba'),
            telefono:  $request->validated('telefono'),
            mensaje:   $request->validated('mensaje'),
        );

        return redirect()
            ->route('arrendador.solicitud')
            ->with('success', 'Solicitud enviada. La revisamos y te avisamos por correo.');
    }

    /**
     * Entrega un documento de verificación. No hay URL pública hacia estos
     * archivos: pasan por aquí para comprobar permisos en cada descarga.
     */
    public function documento(SolicitudArrendador $solicitud, string $tipo): BinaryFileResponse
    {
        $this->authorize('verDocumentos', $solicitud);

        abort_unless(in_array($tipo, ['documento', 'prueba'], true), 404);

        $ruta = $this->verificacion->rutaCompleta(
            $tipo === 'documento' ? $solicitud->documento_path : $solicitud->prueba_path
        );

        abort_if($ruta === null, 404);

        return response()->file($ruta);
    }
}
