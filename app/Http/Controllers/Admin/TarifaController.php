<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ajuste;
use App\Models\Auditoria;
use App\Models\TarifaCampana;
use App\Services\Auditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tarifas de campaña y datos de cobro.
 *
 * Todo esto estaba en config/ y en el .env, o sea que cambiar un precio
 * exigía editar un archivo, hacer commit y volver a desplegar. No sirve:
 * los precios suben en temporada (enero y julio-agosto) y bajan el resto
 * del año, y eso lo decide el administrador, no quien programa.
 */
class TarifaController extends Controller
{
    public function index(): View
    {
        return view('admin.campanas.tarifas', [
            'tarifas' => TarifaCampana::orderBy('orden')->orderBy('dias')->get(),
            'ajustes' => Ajuste::todos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tarifa = TarifaCampana::create($this->validada($request));

        Auditor::registrar(
            Auditoria::TARIFAS_CAMBIADAS,
            sprintf('Creó la tarifa «%s»: %d días por %s', $tarifa->nombre, $tarifa->dias, $tarifa->precioFormateado()),
            $tarifa,
        );

        return back()->with('success', 'Tarifa «' . $tarifa->nombre . '» creada.');
    }

    public function update(Request $request, TarifaCampana $tarifa): RedirectResponse
    {
        $antes = $tarifa->precio;

        $tarifa->update($this->validada($request));

        Auditor::registrar(
            Auditoria::TARIFAS_CAMBIADAS,
            sprintf('Editó la tarifa «%s»', $tarifa->nombre),
            $tarifa,
            ['precio_antes' => $antes, 'precio_ahora' => $tarifa->precio],
        );

        return back()->with('success', 'Tarifa actualizada.');
    }

    public function destroy(TarifaCampana $tarifa): RedirectResponse
    {
        $nombre = $tarifa->nombre;

        /*
         * Borrar la tarifa NO toca las campañas ya cobradas: el monto y
         * los días quedaron copiados en cada cobro. Cambiar un precio no
         * puede reescribir lo que alguien ya pagó.
         */
        $tarifa->delete();

        Auditor::registrar(Auditoria::TARIFAS_CAMBIADAS, 'Eliminó la tarifa «' . $nombre . '»');

        return back()->with('success', 'Tarifa «' . $nombre . '» eliminada. Los cobros ya hechos no cambian.');
    }

    /** Guarda los datos de cobro (a qué Nequi se paga, a qué correo se escribe). */
    public function guardarAjustes(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'cobro_nequi'         => ['nullable', 'string', 'max:40'],
            'cobro_daviplata'     => ['nullable', 'string', 'max:40'],
            'cobro_correo'        => ['nullable', 'email', 'max:120'],
            'cobro_instrucciones' => ['nullable', 'string', 'max:600'],
        ], [], [
            'cobro_nequi'     => 'número de Nequi',
            'cobro_daviplata' => 'número de Daviplata',
            'cobro_correo'    => 'correo',
        ]);

        Ajuste::guardar(Ajuste::COBRO_NEQUI, $datos['cobro_nequi'] ?? null);
        Ajuste::guardar(Ajuste::COBRO_DAVIPLATA, $datos['cobro_daviplata'] ?? null);
        Ajuste::guardar(Ajuste::COBRO_CORREO, $datos['cobro_correo'] ?? null);
        Ajuste::guardar(Ajuste::COBRO_INSTRUCCIONES, $datos['cobro_instrucciones'] ?? null);

        /*
         * En la auditoría NO se guardan los números. Queda que alguien
         * los cambió y cuándo, que es lo que hace falta para detectar que
         * una cuenta ajena se coló; copiarlos ahí solo los duplicaría en
         * un sitio más del que nadie se acuerda de borrarlos.
         */
        Auditor::registrar(Auditoria::COBRO_CONFIGURADO, 'Cambió los datos de cobro de las campañas');

        return back()->with('success', 'Datos de cobro guardados.');
    }

    /** @return array<string, mixed> */
    private function validada(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'dias'   => ['required', 'integer', 'min:1', 'max:365'],
            // Hasta un millón: una campaña cuesta decenas de miles. Un
            // cero de más es un error de dedo, no una venta.
            'precio' => ['required', 'integer', 'min:0', 'max:1000000'],
            'nota'   => ['nullable', 'string', 'max:160'],
            'orden'  => ['required', 'integer', 'min:0', 'max:999'],
        ], [], [
            'nombre' => 'nombre', 'dias' => 'días', 'precio' => 'precio', 'orden' => 'orden',
        ]);

        // Casillas sin marcar no se envían, así que no llegan a
        // validated() y conservarían su valor anterior.
        $datos['activa']    = $request->boolean('activa');
        $datos['destacada'] = $request->boolean('destacada');

        return $datos;
    }
}
