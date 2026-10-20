<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alojamiento;
use App\Models\Rating;
use App\Models\TelefonoVisto;
use App\Models\User;
use App\Services\RespaldoBaseDeDatos;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    /**
     * A partir de cuántos teléfonos en 24 horas se avisa.
     *
     * Una persona buscando dónde vivir mira diez o quince antes de
     * decidirse. Veinte ya es mucho; es el umbral para mirar, no para
     * castigar: el panel solo lo muestra, no bloquea a nadie.
     */
    private const TELEFONOS_SOSPECHOSOS = 20;

    /** Resumen general de la plataforma. */
    public function index(RespaldoBaseDeDatos $respaldos): View
    {
        return view('admin.index', [
            // Cuándo se hizo la última copia de seguridad (RNF-3). Null si
            // no hay ninguna. Que el comando exista no garantiza que corra:
            // hace falta una línea en el cron del servidor, y lo único que
            // demuestra que está puesta es que haya archivos recientes.
            'ultimoRespaldo'    => $this->ultimoRespaldo($respaldos),

            'totalUsuarios'     => User::count(),
            'totalAdmins'       => User::where('role', User::ROLE_ADMIN)->count(),
            'totalAlojamientos' => Alojamiento::count(),
            'totalActivos'      => Alojamiento::activos()->count(),
            'totalResenas'      => Rating::count(),
            'promedioGeneral'   => round((float) Rating::avg('rating'), 1),
            'ultimos'           => Alojamiento::with('user:id,name')->latest()->take(5)->get(),
            'ultimosUsuarios'   => User::latest()->take(5)->get(),

            // Quién pidió más teléfonos en las últimas 24 horas. Un
            // estudiante buscando cuarto mira diez o quince; una cifra muy
            // por encima de eso es alguien recogiendo contactos.
            'cosechadores'      => TelefonoVisto::query()
                ->with('user:id,name,email,created_at')
                ->selectRaw('user_id, count(*) as total, max(created_at) as ultima')
                ->where('created_at', '>=', now()->subDay())
                ->groupBy('user_id')
                ->having('total', '>=', self::TELEFONOS_SOSPECHOSOS)
                ->orderByDesc('total')
                ->take(5)
                ->get(),

            'telefonosHoy'      => TelefonoVisto::where('created_at', '>=', now()->startOfDay())->count(),
        ]);
    }

    private function ultimoRespaldo(RespaldoBaseDeDatos $respaldos): ?Carbon
    {
        $archivos = $respaldos->archivos();

        return $archivos === []
            ? null
            : Carbon::createFromTimestamp(File::lastModified($archivos[0]));
    }
}
