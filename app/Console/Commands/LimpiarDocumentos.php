<?php

namespace App\Console\Commands;

use App\Models\SolicitudArrendador;
use App\Services\VerificacionArrendadorService;
use Illuminate\Console\Command;

/**
 * Borra los documentos de las solicitudes ya resueltas que todavía los
 * conserven.
 *
 * Desde que aprobar o rechazar los elimina automáticamente, esto solo hace
 * falta para las solicitudes resueltas ANTES de ese cambio, o si alguna
 * eliminación falló. Conviene dejarlo programado: una cédula olvidada en el
 * disco no avisa de que está ahí.
 *
 *   php artisan habbi:limpiar-documentos
 */
class LimpiarDocumentos extends Command
{
    protected $signature = 'habbi:limpiar-documentos {--listar : Solo decir cuáles hay, sin borrar nada}';

    protected $description = 'Borra los documentos de verificación de solicitudes ya resueltas';

    public function handle(VerificacionArrendadorService $verificacion): int
    {
        $pendientesDeBorrar = SolicitudArrendador::query()
            ->whereNot('estado', SolicitudArrendador::PENDIENTE)
            ->whereNull('documentos_borrados_en')
            ->with('user:id,name')
            ->get();

        if ($pendientesDeBorrar->isEmpty()) {
            $this->info('No queda ningún documento de solicitudes resueltas.');

            return self::SUCCESS;
        }

        $this->warn(sprintf(
            '%d solicitud(es) resuelta(s) conservan todavía sus documentos:',
            $pendientesDeBorrar->count(),
        ));

        foreach ($pendientesDeBorrar as $s) {
            $this->line(sprintf(
                '  · #%d · %s · %s · resuelta %s',
                $s->id,
                $s->user?->name ?? 'cuenta eliminada',
                $s->estado,
                $s->revisada_en?->diffForHumans() ?? 'sin fecha',
            ));
        }

        if ($this->option('listar')) {
            $this->line('');
            $this->line('Para borrarlos: php artisan habbi:limpiar-documentos');

            return self::SUCCESS;
        }

        $pendientesDeBorrar->each(fn ($s) => $verificacion->eliminarDocumentos($s));

        $this->line('');
        $this->info('Documentos eliminados. Las solicitudes y la auditoría se conservan.');

        return self::SUCCESS;
    }
}
