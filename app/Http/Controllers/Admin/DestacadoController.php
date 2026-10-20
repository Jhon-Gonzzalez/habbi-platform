<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alojamiento;
use App\Models\Destacado;
use App\Services\DestacadoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Cobros por destacar publicaciones (panel de administración).
 *
 * El flujo real: el arrendador paga por Nequi o Daviplata, avisa, y aquí
 * se registra. Es manual a propósito mientras el volumen sea pequeño.
 */
class DestacadoController extends Controller
{
    public function __construct(private readonly DestacadoService $destacados)
    {
    }

    public function index(): View
    {
        $vigentes = Alojamiento::query()
            ->destacados()
            ->with('user:id,name')
            ->orderBy('destacado_hasta')
            ->get();

        $cobros = Destacado::query()
            ->with(['alojamiento:id,title', 'registrador:id,name'])
            ->latest()
            ->paginate(20);

        /*
         * Lo recaudado del mes en curso. Es la única cifra que de verdad
         * dice si esto funciona, así que va arriba y no escondida en los
         * informes.
         */
        $delMes = Destacado::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('monto');

        return view('admin.destacados.index', [
            'vigentes'     => $vigentes,
            'cobros'       => $cobros,
            'delMes'       => (int) $delMes,
            /*
             * El OR va dentro de un grupo. Sin los paréntesis, SQL lo lee
             * como «(aprobada Y activa Y con cupos) O caducada», y acabarían
             * saliendo publicaciones pausadas o pendientes solo por tener un
             * destaque vencido.
             */
            'candidatas'   => Alojamiento::query()
                ->disponibles()
                ->where(fn ($sql) => $sql
                    ->whereNull('destacado_hasta')
                    ->orWhere('destacado_hasta', '<=', now()))
                ->with('user:id,name')
                ->orderBy('title')
                ->get(['id', 'title', 'user_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'alojamiento_id' => ['required', 'exists:alojamientos,id'],
            'dias'           => ['required', 'integer', 'min:1', 'max:90'],
            // Hasta un millón: un destaque cuesta decenas de miles. Un
            // cero de más es un error de dedo, no una venta.
            'monto'          => ['required', 'integer', 'min:0', 'max:1000000'],
            'medio'          => ['required', Rule::in(Destacado::MEDIOS)],
            'referencia'     => ['nullable', 'string', 'max:120'],
            'nota'           => ['nullable', 'string', 'max:500'],
        ], [], [
            'alojamiento_id' => 'publicación',
            'dias'           => 'días',
            'monto'          => 'monto',
            'medio'          => 'medio de pago',
        ]);

        $alojamiento = Alojamiento::findOrFail($datos['alojamiento_id']);

        /*
         * Una publicación pendiente o rechazada no se puede destacar: no
         * se ve en el buscador, así que cobrar por subirla de posición
         * sería cobrar por nada.
         */
        if (! $alojamiento->esVisibleAlPublico()) {
            return back()->withInput()->with('error',
                'Esa publicación no está visible en el buscador (' . $alojamiento->nombreEstado()
                . '), así que destacarla no haría nada. Apruébala primero.');
        }

        $cobro = $this->destacados->activar(
            alojamiento:   $alojamiento,
            dias:          $datos['dias'],
            monto:         $datos['monto'],
            medio:         $datos['medio'],
            referencia:    $datos['referencia'] ?? null,
            nota:          $datos['nota'] ?? null,
            registradoPor: $request->user(),
        );

        return back()->with('success', sprintf(
            '«%s» queda destacada hasta el %s. Cobro registrado: %s por %s.',
            $alojamiento->title,
            $cobro->termina_en->translatedFormat('d \d\e F'),
            $cobro->montoFormateado(),
            $cobro->medio,
        ));
    }

    public function destroy(Request $request, Alojamiento $alojamiento): RedirectResponse
    {
        $this->destacados->quitar($alojamiento, $request->user(), $request->input('motivo'));

        return back()->with('success',
            '«' . $alojamiento->title . '» ya no está destacada. '
            . 'El cobro sigue en el historial: si hay que devolver algo, se hace aparte.');
    }
}
