<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Alojamiento;
use App\Models\Auditoria;
use App\Models\SolicitudCampana;
use App\Models\TarifaCampana;
use App\Services\Auditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * La página donde el arrendador ve qué es una campaña y la solicita.
 *
 * El pago sigue ocurriendo fuera (Nequi, Daviplata, efectivo) porque el
 * volumen no justifica una pasarela todavía. Pero la PETICIÓN ya no es un
 * correo suelto que se puede perder: queda registrada, el administrador la
 * ve en su panel y la aprueba con un clic. Aprobar es lo único que activa
 * la campaña; mientras el pago no esté verificado, no se activa nada.
 */
class CampanaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Alojamiento $alojamiento): View
    {
        $this->authorize('update', $alojamiento);

        return view('alojamientos.campana', [
            'alojamiento'   => $alojamiento,
            'tarifas'       => TarifaCampana::activas()->get(),
            'pendiente'     => $this->solicitudPendiente($alojamiento),
            'ultimas'       => $alojamiento->solicitudesDeCampana()->latest()->take(5)->get(),
            'nequi'         => Ajuste::obtener(Ajuste::COBRO_NEQUI),
            'daviplata'     => Ajuste::obtener(Ajuste::COBRO_DAVIPLATA),
            'correo'        => Ajuste::obtener(Ajuste::COBRO_CORREO, config('habbi.contacto.email')),
            'instrucciones' => Ajuste::obtener(Ajuste::COBRO_INSTRUCCIONES),
        ]);
    }

    /** Registra la solicitud. No activa nada: eso lo hace el administrador. */
    public function store(Request $request, Alojamiento $alojamiento): RedirectResponse
    {
        $this->authorize('update', $alojamiento);

        // Cobrar por subir de posición algo que nadie puede ver sería
        // cobrar por nada.
        abort_unless($alojamiento->esVisibleAlPublico(), 403);

        if ($this->solicitudPendiente($alojamiento)) {
            return back()->with('error', 'Ya tiene una solicitud pendiente para esta publicación.');
        }

        $datos = $request->validate(
            [
                'tarifa_id'  => ['required', 'integer', 'exists:tarifas_campana,id'],
                'medio'      => ['required', 'string', 'in:' . implode(',', \App\Models\Campana::MEDIOS)],
                'referencia' => ['nullable', 'string', 'max:60'],
                // El comprobante es obligatorio: sin él no hay nada que
                // verificar y la solicitud se queda parada esperando un
                // correo que a lo mejor nunca llega.
                'comprobante' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
                'nota'        => ['nullable', 'string', 'max:500'],
            ],
            ['comprobante.required' => 'Adjunte el comprobante del pago: es lo que verificamos.'],
            ['tarifa_id' => 'plan', 'medio' => 'medio de pago', 'comprobante' => 'comprobante'],
        );

        $tarifa = TarifaCampana::activas()->findOrFail($datos['tarifa_id']);

        // El precio se copia ahora: si la tarifa sube mañana, esta
        // solicitud tiene que seguir diciendo lo que se pidió hoy.
        $solicitud = $alojamiento->solicitudesDeCampana()->create([
            'solicitada_por' => $request->user()->id,
            'tarifa_id'      => $tarifa->id,
            'nombre_tarifa'  => $tarifa->nombre,
            'dias'           => $tarifa->dias,
            'monto'          => $tarifa->precio,
            'medio'          => $datos['medio'],
            'referencia'     => $datos['referencia'] ?? null,
            // Disco «private»: es un documento bancario de una persona, no
            // puede quedar en una URL que adivine cualquiera.
            'comprobante'    => $request->file('comprobante')->store('comprobantes', 'private'),
            'nota'           => $datos['nota'] ?? null,
            'estado'         => SolicitudCampana::PENDIENTE,
        ]);

        Auditor::registrar(
            Auditoria::CAMPANA_SOLICITADA,
            sprintf(
                'Solicitó campaña «%s» (%d días · %s) para «%s»',
                $solicitud->nombre_tarifa,
                $solicitud->dias,
                $solicitud->montoFormateado(),
                $alojamiento->title,
            ),
            $alojamiento,
        );

        return back()->with('ok', 'Solicitud enviada. La activaremos en cuanto verifiquemos el pago.');
    }

    /**
     * Sirve el comprobante al dueño de la publicación o a un administrador.
     *
     * No se expone por URL pública: el archivo vive fuera de public/ y se
     * entrega aquí, después de comprobar quién lo pide.
     */
    public function comprobante(Request $request, SolicitudCampana $solicitud): BinaryFileResponse
    {
        $usuario = $request->user();

        abort_unless(
            $usuario->isAdmin() || $solicitud->alojamiento->user_id === $usuario->id,
            403,
        );

        abort_if($solicitud->comprobante === null, 404);
        abort_unless(Storage::disk('private')->exists($solicitud->comprobante), 404);

        return response()->file(Storage::disk('private')->path($solicitud->comprobante));
    }

    private function solicitudPendiente(Alojamiento $alojamiento): ?SolicitudCampana
    {
        return $alojamiento->solicitudesDeCampana()->pendientes()->latest()->first();
    }
}
