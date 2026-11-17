<?php

namespace App\Http\Controllers;

use App\Models\Alojamiento;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Services\ChatService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;

/** Mensajería entre estudiantes y arrendadores. */
class ConversacionController extends Controller
{
    public function __construct(private readonly ChatService $chat)
    {
        $this->middleware('auth');
    }

    /** Bandeja: todas las conversaciones de quien mira, por actividad. */
    public function index(Request $request): View
    {
        $conversaciones = Conversacion::de($request->user())
            ->with(['alojamiento:id,title,user_id,cupos,is_active', 'estudiante:id,name,foto_perfil', 'arrendador:id,name,foto_perfil'])
            ->paginate(20);

        return view('mensajes.index', compact('conversaciones'));
    }

    public function show(Request $request, Conversacion $conversacion): View
    {
        $this->autorizar($request, $conversacion);

        // Al abrir el hilo se marcan como leídos los del otro.
        $conversacion->mensajes()
            ->whereNot('user_id', $request->user()->id)
            ->whereNull('leido_en')
            ->update(['leido_en' => now()]);

        return view('mensajes.show', [
            'conversacion' => $conversacion->load(['mensajes.autor:id,name,foto_perfil', 'alojamiento.user:id,name']),
            'otro'         => $conversacion->otro($request->user()),
        ]);
    }

    /** Abre (o recupera) la conversación desde la ficha del alojamiento. */
    public function abrir(Request $request, Alojamiento $alojamiento): RedirectResponse
    {
        if ($alojamiento->user_id === $request->user()->id) {
            return back()->with('error', 'No puedes escribirte a ti mismo sobre tu propia publicación.');
        }

        $conversacion = $this->chat->abrir($alojamiento, $request->user());

        return redirect()->route('mensajes.show', $conversacion);
    }

    public function enviar(Request $request, Conversacion $conversacion): RedirectResponse
    {
        $this->autorizar($request, $conversacion);

        /*
         * El cuerpo deja de ser obligatorio SI viene un archivo: mandar una
         * foto del cuarto sin escribir nada es un mensaje perfectamente
         * válido, y obligar a poner «mira» no aporta nada.
         */
        $datos = $request->validate(
            [
                'cuerpo'  => ['required_without:adjunto', 'nullable', 'string', 'min:2', 'max:2000'],
                'adjunto' => [
                    'nullable', 'file',
                    'mimes:' . implode(',', Mensaje::TIPOS_ADJUNTO),
                    'max:5120',
                ],
            ],
            [
                'cuerpo.required_without' => 'Escriba algo o adjunte un archivo.',
                'adjunto.mimes'           => 'Se admiten fotos (JPG, PNG, WEBP) y PDF.',
                'adjunto.max'             => 'El archivo no puede pasar de 5 MB.',
            ],
            ['cuerpo' => 'mensaje', 'adjunto' => 'archivo'],
        );

        $this->chat->enviar(
            $conversacion,
            $request->user(),
            $datos['cuerpo'] ?? '',
            $request->file('adjunto'),
        );

        return redirect()->route('mensajes.show', $conversacion);
    }

    /* ===================== El acuerdo ===================== */

    public function proponer(Request $request, Conversacion $conversacion): RedirectResponse
    {
        $this->autorizar($request, $conversacion);

        if ($conversacion->acuerdoConfirmado()) {
            return back()->with('error', 'Este acuerdo ya está cerrado.');
        }

        if ($conversacion->alojamiento->estaArrendado()) {
            return back()->with('error', 'Esta publicación ya no tiene plazas libres.');
        }

        $datos = $request->validate([
            'personas' => ['required', 'integer', 'min:1', 'max:' . $conversacion->alojamiento->cupos],
        ], [
            'personas.max' => 'Solo quedan ' . $conversacion->alojamiento->cupos . ' plaza(s) libre(s).',
        ], ['personas' => 'número de plazas']);

        $this->chat->proponerAcuerdo($conversacion, $request->user(), (int) $datos['personas']);

        return back()->with('success', 'Propuesta enviada. Falta que la otra persona la confirme.');
    }

    public function confirmar(Request $request, Conversacion $conversacion): RedirectResponse
    {
        $this->autorizar($request, $conversacion);

        // La regla está en el modelo: confirma quien NO propuso.
        if (! $conversacion->puedeConfirmar($request->user())) {
            return back()->with('error', 'El acuerdo lo tiene que confirmar la otra persona, no quien lo propuso.');
        }

        $this->chat->confirmarAcuerdo($conversacion, $request->user());

        return back()->with('success', '¡Acuerdo cerrado! La publicación ya se actualizó.');
    }

    public function retirar(Request $request, Conversacion $conversacion): RedirectResponse
    {
        $this->autorizar($request, $conversacion);

        if (! $conversacion->puedeRetirar($request->user())) {
            return back()->with('error', 'Solo quien propuso el acuerdo puede retirarlo.');
        }

        $this->chat->retirarAcuerdo($conversacion, $request->user());

        return back()->with('success', 'Propuesta retirada.');
    }

    /**
     * Solo los dos participantes. Ni siquiera un administrador entra aquí:
     * una conversación privada no es material de moderación mientras nadie
     * la reporte.
     */
    /**
     * Entrega el adjunto de un mensaje a los DOS participantes y a nadie más.
     *
     * Usa la misma comprobación que leer el hilo, a propósito: un
     * administrador no puede leer conversaciones ajenas, así que tampoco
     * puede abrir lo que se mandaron por dentro.
     */
    public function adjunto(Request $request, Conversacion $conversacion, Mensaje $mensaje): BinaryFileResponse
    {
        $this->autorizar($request, $conversacion);

        abort_unless($mensaje->conversacion_id === $conversacion->id, 404);
        abort_unless($mensaje->tieneAdjunto(), 404);
        abort_unless(Storage::disk('private')->exists($mensaje->adjunto), 404);

        return response()->file(
            Storage::disk('private')->path($mensaje->adjunto),
            ['Content-Disposition' => 'inline; filename="' . addslashes($mensaje->adjunto_nombre) . '"'],
        );
    }

    private function autorizar(Request $request, Conversacion $conversacion): void
    {
        abort_unless($conversacion->participa($request->user()), 403);
    }
}
