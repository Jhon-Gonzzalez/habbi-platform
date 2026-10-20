<?php

namespace App\Http\Controllers;

use App\Models\Alojamiento;
use App\Models\Conversacion;
use App\Services\ChatService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $datos = $request->validate(
            ['cuerpo' => ['required', 'string', 'min:2', 'max:2000']],
            ['cuerpo.required' => 'Escribe algo antes de enviar.'],
            ['cuerpo' => 'mensaje'],
        );

        $this->chat->enviar($conversacion, $request->user(), $datos['cuerpo']);

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
    private function autorizar(Request $request, Conversacion $conversacion): void
    {
        abort_unless($conversacion->participa($request->user()), 403);
    }
}
