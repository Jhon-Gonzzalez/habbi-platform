<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Alojamiento;
use App\Models\TarifaCampana;
use Illuminate\Contracts\View\View;

/**
 * La página donde el arrendador ve qué es una campaña y cómo pedirla.
 *
 * No cobra nada: muestra las tarifas que el administrador tenga puestas
 * y los datos de pago. El pago ocurre por Nequi o Daviplata y lo
 * registra un administrador. Es un paso manual a propósito mientras el
 * volumen no justifique una pasarela.
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
            'alojamiento' => $alojamiento,
            'tarifas'     => TarifaCampana::activas()->get(),
            'nequi'       => Ajuste::obtener(Ajuste::COBRO_NEQUI),
            'daviplata'   => Ajuste::obtener(Ajuste::COBRO_DAVIPLATA),
            'correo'      => Ajuste::obtener(Ajuste::COBRO_CORREO, config('habbi.contacto.email')),
            'instrucciones' => Ajuste::obtener(Ajuste::COBRO_INSTRUCCIONES),
        ]);
    }
}
