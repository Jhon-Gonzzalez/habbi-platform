<?php

namespace App\Http\Controllers;

use App\Models\Alojamiento;
use App\Models\TelefonoVisto;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Revela el teléfono de un alojamiento, dejando constancia de quién lo pidió.
 *
 * Responde JSON si quien pregunta es la propia página (fetch), y una página
 * normal si no hay JavaScript. Las dos rutas registran igual: la protección
 * no puede depender de que el navegador colabore.
 */
class TelefonoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function __invoke(Request $request, Alojamiento $alojamiento): JsonResponse|View
    {
        if (! $alojamiento->telefonoLimpio()) {
            abort(404);
        }

        // El dueño y los administradores no cuentan: ver el propio teléfono
        // o moderar no es cosechar datos, y ensuciaría las estadísticas.
        if (! $this->esPropioOAdmin($request, $alojamiento)) {
            TelefonoVisto::create([
                'user_id'        => $request->user()->id,
                'alojamiento_id' => $alojamiento->id,
                'ip'             => $request->ip(),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'telefono' => $alojamiento->phone,
                'limpio'   => $alojamiento->telefonoLimpio(),
                'whatsapp' => $this->enlaceWhatsapp($alojamiento),
            ]);
        }

        return view('alojamientos.telefono', [
            'alojamiento' => $alojamiento,
            'whatsapp'    => $this->enlaceWhatsapp($alojamiento),
        ]);
    }

    private function esPropioOAdmin(Request $request, Alojamiento $alojamiento): bool
    {
        return $request->user()->id === $alojamiento->user_id
            || $request->user()->isAdmin();
    }

    private function enlaceWhatsapp(Alojamiento $alojamiento): string
    {
        $mensaje = 'Hola, vi tu alojamiento "' . $alojamiento->title . '" en HABBI y me interesa.';

        return 'https://wa.me/' . $alojamiento->telefonoLimpio() . '?text=' . urlencode($mensaje);
    }
}
