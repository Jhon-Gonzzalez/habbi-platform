<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alojamiento;
use App\Models\Rating;
use App\Models\TelefonoVisto;
use App\Models\User;
use App\Support\Texto;
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

            /*
             * Cosas que están mal y que alguien puede arreglar hoy.
             *
             * El panel enseñaba cifras (cuántos usuarios, cuántas
             * publicaciones) que están bien para presumir pero no le
             * dicen a nadie qué hacer. Esto sí: cada una es una lista de
             * publicaciones con un problema concreto y un enlace para ir
             * a verlas.
             */
            'pendientes'        => $this->pendientesDeAtencion(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pendientesDeAtencion(): array
    {
        $avisos = [];

        /*
         * Sin coordenadas no salen en el filtro por distancia, que es
         * como busca la mayoría. Es el fallo más caro de todos porque
         * no se nota: la publicación existe, se ve en el listado, y
         * simplemente nunca aparece cuando alguien busca «a menos de
         * 2 km de la universidad».
         */
        $sinPunto = Alojamiento::disponibles()->whereNull('lat')->count();

        if ($sinPunto) {
            $avisos[] = [
                'texto'  => Texto::plural('publicación', $sinPunto) . ' sin punto en el mapa: '
                    . 'no aparecen cuando alguien busca por cercanía a la universidad',
                'numero' => $sinPunto,
                'url'    => route('admin.publicaciones.index', ['estado' => 'todas']),
                'grave'  => true,
            ];
        }

        $sinFoto = Alojamiento::disponibles()
            ->where(fn ($q) => $q->whereNull('cover_path')->orWhere('cover_path', ''))
            ->count();

        if ($sinFoto) {
            $avisos[] = [
                'texto'  => Texto::plural('publicación', $sinFoto) . ' sin foto de portada',
                'numero' => $sinFoto,
                'url'    => route('admin.publicaciones.index', ['estado' => 'todas']),
                'grave'  => false,
            ];
        }

        /*
         * Publicadas por alguien a quien nadie verificó. No debería
         * pasar —para publicar hay que estar verificado— pero puede
         * quedar así si a un arrendador se le revoca la condición
         * después de haber publicado.
         */
        $deNoVerificados = Alojamiento::disponibles()
            ->whereHas('user', fn ($q) => $q->whereNull('verificado_en'))
            ->count();

        if ($deNoVerificados) {
            $avisos[] = [
                'texto'  => Texto::plural('publicación', $deNoVerificados)
                    . ' de arrendadores sin verificar',
                'numero' => $deNoVerificados,
                'url'    => route('admin.publicaciones.index', ['estado' => 'todas']),
                'grave'  => true,
            ];
        }

        return $avisos;
    }

    private function ultimoRespaldo(RespaldoBaseDeDatos $respaldos): ?Carbon
    {
        $archivos = $respaldos->archivos();

        return $archivos === []
            ? null
            : Carbon::createFromTimestamp(File::lastModified($archivos[0]));
    }
}
