<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        /*
         * Copia de seguridad diaria (RNF-3).
         *
         * De madrugada y en hora de Colombia, que es cuando nadie está
         * buscando habitación. La hora exacta sale de la configuración por
         * si el servidor ya tiene otra tarea pesada a esa hora.
         *
         * withoutOverlapping: si una noche el volcado tarda más de un día
         * (no debería, pero), no se encadenan dos a la vez peleándose por
         * el disco. onOneServer no hace falta todavía porque hay un solo
         * servidor; el día que haya dos, hay que añadirlo.
         *
         * Para que esto corra de verdad, el servidor necesita una línea en
         * el cron del sistema. Está en docs/respaldos.md.
         */
        $schedule->command('habbi:respaldar')
            ->dailyAt(config('habbi.respaldos.hora'))
            ->timezone('America/Bogota')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/respaldos.log'));

        /*
         * Barrido de documentos de identidad olvidados.
         *
         * Aprobar o rechazar una solicitud ya los borra; esto solo recoge
         * los que se hayan quedado atrás porque un borrado falló. Una
         * cédula escaneada olvidada en el disco no avisa de que está ahí.
         */
        $schedule->command('habbi:limpiar-documentos')
            ->dailyAt('04:10')
            ->timezone('America/Bogota');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
