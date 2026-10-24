<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alojamiento;
use App\Models\Campana;
use App\Models\TarifaCampana;
use App\Services\CampanaService;
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
class CampanaController extends Controller
{
    public function __construct(private readonly CampanaService $destacados)
    {
    }

    public function index(): View
    {
        $vigentes = Alojamiento::query()
            ->conCampana()
            ->with('user:id,name')
            ->orderBy('campana_hasta')
            ->get();

        $cobros = Campana::query()
            ->with(['alojamiento:id,title', 'registrador:id,name'])
            ->latest()
            ->paginate(20);

        /*
         * Lo recaudado del mes en curso. Es la única cifra que de verdad
         * dice si esto funciona, así que va arriba y no escondida en los
         * informes.
         */
        $delMes = Campana::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('monto');

        return view('admin.campanas.index', [
            'tarifas'      => TarifaCampana::activas()->get(),
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
                    ->whereNull('campana_hasta')
                    ->orWhere('campana_hasta', '<=', now()))
                ->with('user:id,name')
                ->orderBy('title')
                ->get(['id', 'title', 'user_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /*
         * O se elige una tarifa, o se escriben días y monto a mano.
         *
         * Lo segundo hace falta de verdad: un arrendador que paga a
         * medias, un descuento puntual o una campaña de regalo en
         * temporada baja no caben en ninguna tarifa, y obligar a crear
         * una tarifa falsa para cada caso ensuciaría la lista de precios
         * que ven todos.
         */
        $datos = $request->validate([
            'alojamiento_id' => ['required', 'exists:alojamientos,id'],
            'tarifa_id'      => ['nullable', 'exists:tarifas_campana,id'],
            'dias'           => ['required_without:tarifa_id', 'nullable', 'integer', 'min:1', 'max:365'],
            // Hasta un millón: una campaña cuesta decenas de miles. Un
            // cero de más es un error de dedo, no una venta.
            'monto'          => ['required_without:tarifa_id', 'nullable', 'integer', 'min:0', 'max:1000000'],
            'medio'          => ['required', Rule::in(Campana::MEDIOS)],
            'referencia'     => ['nullable', 'string', 'max:120'],
            'nota'           => ['nullable', 'string', 'max:500'],
        ], [
            'dias.required_without'  => 'Elige una tarifa o escribe cuántos días.',
            'monto.required_without' => 'Elige una tarifa o escribe cuánto pagó.',
        ], [
            'alojamiento_id' => 'publicación',
            'tarifa_id'      => 'tarifa',
            'dias'           => 'días',
            'monto'          => 'monto',
            'medio'          => 'medio de pago',
        ]);

        /*
         * Los días y el monto se COPIAN al cobro, no se referencian.
         * Así, el día que suba el precio de la quincena, lo que ya se
         * cobró sigue diciendo lo que de verdad se pagó.
         */
        if (! empty($datos['tarifa_id'])) {
            $tarifa = TarifaCampana::findOrFail($datos['tarifa_id']);

            $datos['dias']  ??= $tarifa->dias;
            $datos['monto'] ??= $tarifa->precio;
        }

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
