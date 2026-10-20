<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Services\Auditor;
use App\Services\RespaldoBaseDeDatos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Copia de seguridad de la base de datos (RNF-3).
 *
 *   php artisan habbi:respaldar            # respalda y rota
 *   php artisan habbi:respaldar --listar   # solo decir qué hay guardado
 *   php artisan habbi:respaldar --dias=30  # conservar 30 días en vez de 14
 *
 * El programador de tareas lo ejecuta solo cada noche; ver app/Console/Kernel.php.
 */
class Respaldar extends Command
{
    protected $signature = 'habbi:respaldar
        {--listar : Solo mostrar los respaldos que ya existen}
        {--dias= : Días de respaldos a conservar (por defecto, lo que diga la configuración)}';

    protected $description = 'Hace una copia de seguridad de la base de datos y borra las caducadas';

    public function handle(RespaldoBaseDeDatos $respaldos): int
    {
        if ($this->option('listar')) {
            return $this->listar($respaldos);
        }

        $dias = (int) ($this->option('dias') ?: config('habbi.respaldos.dias'));

        try {
            $archivo = $respaldos->crear();
        } catch (RuntimeException $e) {
            /*
             * Se registra además de mostrarse: cuando esto lo ejecuta el
             * programador de tareas no hay nadie leyendo la pantalla, y un
             * respaldo que lleva semanas fallando en silencio es lo mismo
             * que no tener respaldos.
             */
            Log::error('Falló el respaldo de la base de datos: ' . $e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Respaldo creado: %s (%s)',
            basename($archivo),
            $this->tamano($archivo),
        ));

        Auditor::registrar(
            Auditoria::RESPALDO_CREADO,
            'Copia de seguridad de la base de datos: ' . basename($archivo),
            datos: [
                'archivo' => basename($archivo),
                'bytes'   => filesize($archivo) ?: 0,
            ],
        );

        $borrados = $respaldos->rotar($dias);

        if ($borrados !== []) {
            $this->line(sprintf(
                '  Borrados %d respaldo(s) con más de %d día(s): %s',
                count($borrados),
                $dias,
                implode(', ', $borrados),
            ));
        }

        $this->comment("  Carpeta: {$respaldos->carpeta()}");
        $this->comment('  Recuerda que esto NO sustituye a una copia fuera del servidor. '
            . 'Está explicado en docs/respaldos.md.');

        return self::SUCCESS;
    }

    private function listar(RespaldoBaseDeDatos $respaldos): int
    {
        $archivos = $respaldos->archivos();

        if ($archivos === []) {
            $this->warn('No hay ningún respaldo guardado todavía.');
            $this->line("  Carpeta esperada: {$respaldos->carpeta()}");

            return self::SUCCESS;
        }

        $this->info(sprintf('%d respaldo(s) en %s:', count($archivos), $respaldos->carpeta()));

        foreach ($archivos as $ruta) {
            $this->line(sprintf(
                '  %s  %8s  %s',
                date('Y-m-d H:i', filemtime($ruta)),
                $this->tamano($ruta),
                basename($ruta),
            ));
        }

        return self::SUCCESS;
    }

    private function tamano(string $ruta): string
    {
        $bytes = filesize($ruta) ?: 0;

        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : max(1, (int) round($bytes / 1024)) . ' KB';
    }
}
