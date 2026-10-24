<?php

namespace App\View\Composers;

use App\Models\Alojamiento;
use App\Models\Reporte;
use App\Models\SolicitudArrendador;
use Illuminate\View\View;

/**
 * Los contadores de las pastillas del menú del panel.
 *
 * Estaban escritos dentro de layouts/admin.blade.php: cuatro consultas
 * sueltas en medio del HTML. Funcionaba, pero una consulta escondida en
 * una plantilla no se puede probar, no aparece cuando buscas de dónde
 * sale una cifra, y es justo el sitio donde los problemas de
 * rendimiento crecen sin que nadie los vea.
 *
 * Aquí viven juntas, se pueden probar, y el día que haya que cachearlas
 * se cachean en un solo sitio.
 */
class MenuAdmin
{
    public function compose(View $vista): void
    {
        $vista->with([
            'menuSolicitudes' => SolicitudArrendador::pendientes()->count(),
            'menuRevision'    => Alojamiento::pendientes()->count(),
            'menuCampanas'    => Alojamiento::conCampana()->count(),
            'menuReportes'    => Reporte::abiertos()->count(),
        ]);
    }
}
