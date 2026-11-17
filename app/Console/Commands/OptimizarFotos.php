<?php

namespace App\Console\Commands;

use App\Models\Alojamiento;
use App\Services\ImagenOptimizada;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Reduce y recomprime las fotos YA SUBIDAS.
 *
 * Desde que AlojamientoPhotoService optimiza al guardar, lo que entra
 * nuevo pesa poco. Pero lo que se subió antes sigue en el disco tal como
 * salió del celular: hasta 5 MB por foto, ocho por publicación. Cada
 * estudiante que abre esa ficha se los descarga enteros.
 *
 * Se escribe un archivo nuevo en vez de pisar el viejo porque el formato
 * cambia: un .png recomprimido a JPEG servido con extensión .png se envía
 * con el tipo equivocado. La ruta nueva lleva .jpg, la fila se actualiza
 * y el archivo viejo se borra.
 *
 *   php artisan habbi:optimizar-fotos --probar   # solo mirar
 *   php artisan habbi:optimizar-fotos            # hacerlo
 */
class OptimizarFotos extends Command
{
    protected $signature = 'habbi:optimizar-fotos
                            {--probar : Solo informar cuánto se ahorraría, sin tocar nada}';

    protected $description = 'Reduce las fotos de alojamientos que ya estaban subidas';

    private const DISCO = 'public';

    public function handle(ImagenOptimizada $optimizador): int
    {
        $probar = (bool) $this->option('probar');
        $disco  = Storage::disk(self::DISCO);

        $alojamientos = Alojamiento::query()
            ->where(fn ($q) => $q->whereNotNull('photos')->where('photos', '!=', '[]'))
            ->get();

        if ($alojamientos->isEmpty()) {
            $this->info('No hay ninguna publicación con fotos.');

            return self::SUCCESS;
        }

        $antes = $despues = $tocadas = 0;
        $barra = $this->output->createProgressBar($alojamientos->count());
        $barra->start();

        foreach ($alojamientos as $alojamiento) {
            $nuevas  = [];
            $cambios = false;

            foreach ((array) $alojamiento->photos as $ruta) {
                if (! $disco->exists($ruta)) {
                    // La fila apunta a un archivo que ya no está. No es
                    // tarea de este comando arreglarlo, pero tampoco se
                    // pierde la ruta por si alguien la restaura.
                    $nuevas[] = $ruta;
                    continue;
                }

                $original = $disco->get($ruta);
                $peso     = strlen($original);
                $antes   += $peso;

                $jpeg = $optimizador->comprimir($original);

                // Si no se pudo, o si ya estaba bien y recomprimir no gana
                // nada, se deja como está. Recomprimir un JPEG una y otra
                // vez solo le quita calidad.
                if ($jpeg === null || strlen($jpeg) >= $peso * 0.9) {
                    $nuevas[]  = $ruta;
                    $despues  += $peso;
                    continue;
                }

                $despues += strlen($jpeg);
                $tocadas++;

                if ($probar) {
                    $nuevas[] = $ruta;
                    continue;
                }

                $destino = preg_replace('/\.[^.\/]+$/', '', $ruta) . '-opt.jpg';
                $disco->put($destino, $jpeg);
                $disco->delete($ruta);

                $nuevas[] = $destino;
                $cambios  = true;

                if ($alojamiento->cover_path === $ruta) {
                    $alojamiento->cover_path = $destino;
                }
            }

            if ($cambios) {
                $alojamiento->forceFill(['photos' => $nuevas, 'cover_path' => $alojamiento->cover_path])->save();
            }

            $barra->advance();
        }

        $barra->finish();
        $this->newLine(2);

        $mb = fn (int $b) => number_format($b / 1048576, 1) . ' MB';

        if ($tocadas === 0) {
            $this->info('Todas las fotos ya estaban optimizadas.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d fotos: %s → %s (%d%% menos)',
            $probar ? 'Se reducirían' : 'Reducidas',
            $tocadas,
            $mb($antes),
            $mb($despues),
            $antes > 0 ? round((1 - $despues / $antes) * 100) : 0,
        ));

        if ($probar) {
            $this->line('  Nada se ha tocado. Quita --probar para hacerlo de verdad.');
        }

        return self::SUCCESS;
    }
}
