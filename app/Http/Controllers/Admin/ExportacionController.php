<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alojamiento;
use App\Models\Auditoria;
use App\Models\Campana;
use App\Models\User;
use App\Services\Auditor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descargar en CSV lo que el administrador necesita fuera del panel.
 *
 * El PDF de los informes sirve para enseñar; esto sirve para trabajar:
 * cuadrar los cobros de las campañas en una hoja de cálculo, cruzar la
 * lista de arrendadores con otra, o guardar una copia de los datos antes
 * de un cambio grande.
 *
 * Se envía en streaming y no se arma en memoria: una exportación de
 * diez mil filas en una variable tumba un hosting compartido, y es
 * justo el día que más datos hay cuando más falta hace que funcione.
 */
class ExportacionController extends Controller
{
    /** Lo que se puede exportar, y qué saca cada uno. */
    private const TIPOS = ['publicaciones', 'usuarios', 'campanas'];

    public function __invoke(Request $request, string $tipo): StreamedResponse
    {
        abort_unless(in_array($tipo, self::TIPOS, true), 404);

        Auditor::registrar(
            Auditoria::DATOS_EXPORTADOS,
            'Descargó un CSV de ' . $tipo,
            datos: ['tipo' => $tipo],
        );

        $nombre = 'habbi-' . $tipo . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(
            fn () => $this->escribir($tipo),
            $nombre,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    private function escribir(string $tipo): void
    {
        $salida = fopen('php://output', 'w');

        /*
         * El BOM es lo que hace que Excel en Windows no destroce las
         * tildes. Sin él, «Montería» se abre como «MonterÃ­a» y el
         * primero que lo vea va a pensar que los datos están mal.
         */
        fwrite($salida, "\xEF\xBB\xBF");

        match ($tipo) {
            'publicaciones' => $this->publicaciones($salida),
            'usuarios'      => $this->usuarios($salida),
            'campanas'      => $this->campanas($salida),
        };

        fclose($salida);
    }

    /** @param resource $salida */
    private function publicaciones($salida): void
    {
        fputcsv($salida, [
            'ID', 'Título', 'Tipo', 'Precio', 'Periodo', 'Huéspedes', 'Plazas libres',
            'Ciudad', 'Barrio', 'Latitud', 'Longitud', 'Estado', 'Visible',
            'Arrendador', 'Correo del arrendador', 'Reseñas', 'Promedio', 'Publicada',
        ]);

        Alojamiento::with('user:id,name,email')
            ->conResenas()
            ->chunk(200, function ($alojamientos) use ($salida) {
                foreach ($alojamientos as $a) {
                    fputcsv($salida, [
                        $a->id, $a->title, $a->type, $a->price, $a->price_period,
                        $a->guests, $a->cupos, $a->city, $a->neighborhood,
                        $a->lat, $a->lng, $a->nombreEstado(), $a->is_active ? 'sí' : 'no',
                        $a->user?->name, $a->user?->email,
                        $a->ratings_count, $a->ratings_avg_rating ? round($a->ratings_avg_rating, 2) : '',
                        $a->created_at->format('Y-m-d'),
                    ]);
                }
            });
    }

    /** @param resource $salida */
    private function usuarios($salida): void
    {
        /*
         * Aquí NO van las contraseñas, ni siquiera su hash. Un CSV
         * acaba en la carpeta de descargas, en un correo o en un USB, y
         * un hash filtrado se puede atacar sin prisa y sin que nadie se
         * entere. Tampoco van los documentos de identidad.
         */
        fputcsv($salida, [
            'ID', 'Nombre', 'Correo', 'Rol', 'Verificado como arrendador',
            'Correo verificado', 'Ciudad', 'Publicaciones', 'Reseñas escritas', 'Registro',
        ]);

        User::withCount(['alojamientos', 'ratings'])
            ->chunk(200, function ($usuarios) use ($salida) {
                foreach ($usuarios as $u) {
                    fputcsv($salida, [
                        $u->id, $u->name, $u->email, $u->nombreRol(),
                        $u->estaVerificado() ? 'sí' : 'no',
                        $u->email_verified_at ? 'sí' : 'no',
                        $u->ciudad, $u->alojamientos_count, $u->ratings_count,
                        $u->created_at->format('Y-m-d'),
                    ]);
                }
            });
    }

    /** @param resource $salida */
    private function campanas($salida): void
    {
        fputcsv($salida, [
            'ID', 'Fecha del cobro', 'Publicación', 'Arrendador',
            'Días', 'Monto', 'Medio', 'Referencia', 'Empieza', 'Termina', 'Registrado por',
        ]);

        Campana::with(['alojamiento:id,title,user_id', 'alojamiento.user:id,name', 'registrador:id,name'])
            ->chunk(200, function ($cobros) use ($salida) {
                foreach ($cobros as $c) {
                    fputcsv($salida, [
                        $c->id, $c->created_at->format('Y-m-d'),
                        $c->alojamiento?->title, $c->alojamiento?->user?->name,
                        $c->dias, $c->monto, $c->medio, $c->referencia,
                        $c->empieza_en->format('Y-m-d'), $c->termina_en->format('Y-m-d'),
                        $c->registrador?->name,
                    ]);
                }
            });
    }
}
