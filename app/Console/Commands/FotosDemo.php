<?php

namespace App\Console\Commands;

use App\Models\Alojamiento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Reparte un puñado de fotos entre los alojamientos de ejemplo.
 *
 * El seeder de Montería deja las publicaciones SIN fotos a propósito: no se
 * copian imágenes de pensiones reales porque son de alguien y porque esas
 * casas no han dado permiso para aparecer aquí. Pero una demo sin fotos se
 * ve rota, así que este comando toma las que tú descargues de un banco de
 * imágenes libre (Pexels, Unsplash, Openverse) y las reparte.
 *
 * Las fotos se redimensionan a 1600px de ancho y se recomprimen: una foto de
 * banco pesa 4–6 MB y en hosting compartido eso se nota tanto en el disco
 * como en los datos móviles de quien abre la página desde Montería.
 *
 *   1. Descarga 8–12 fotos de habitaciones con licencia libre
 *   2. Súbelas a  storage/app/fotos-demo/
 *   3. php artisan habbi:fotos-demo
 */
class FotosDemo extends Command
{
    protected $signature = 'habbi:fotos-demo
                            {--carpeta=database/seeders/fotos : Carpeta con las fotos de origen, relativa a la raíz del proyecto o a storage/app}
                            {--por=3 : Cuántas fotos recibe cada alojamiento}
                            {--forzar : Reemplazar también las fotos de los que ya tienen}';

    protected $description = 'Reparte fotos de licencia libre entre los alojamientos de ejemplo';

    /** Ancho máximo. Más que esto no aporta nada en pantalla y sí pesa. */
    private const ANCHO_MAX = 1600;

    /** Calidad JPEG. 82 es el punto donde deja de notarse la pérdida. */
    private const CALIDAD = 82;

    private const DESTINO = 'alojamientos/demo';

    public function handle(): int
    {
        $origen = $this->resolverOrigen((string) $this->option('carpeta'));

        if ($origen === null) {
            $this->error('No encontré la carpeta: ' . $this->option('carpeta'));
            $this->line('  Se busca primero en la raíz del proyecto y luego en storage/app.');

            return self::FAILURE;
        }

        $fuentes = $this->leerFotos($origen);

        if ($fuentes === []) {
            $this->error("No encontré imágenes válidas en {$origen}");
            $this->line('  Se aceptan .jpg, .jpeg, .png y .webp');

            return self::FAILURE;
        }

        $this->info(sprintf('Encontré %d fotos en %s', count($fuentes), $origen));

        $procesadas = $this->procesar($fuentes);

        if ($procesadas === []) {
            $this->error('Ninguna foto se pudo procesar.');

            return self::FAILURE;
        }

        return $this->repartir($procesadas);
    }

    /**
     * Encuentra la carpeta de origen.
     *
     * Se admiten las dos: una del repositorio (las fotos de ejemplo viajan
     * con el código y llegan solas al desplegar) y una de storage/app, que
     * git ignora y sirve para las que uno suba a mano al servidor.
     */
    private function resolverOrigen(string $carpeta): ?string
    {
        $carpeta = trim($carpeta, '/');

        foreach ([base_path($carpeta), storage_path('app/' . $carpeta)] as $ruta) {
            if (is_dir($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    /**
     * Lee la carpeta y se queda solo con lo que de verdad es una imagen.
     *
     * No basta con mirar la extensión: un .jpg puede ser cualquier cosa, y
     * GD peta feo con un archivo que no es lo que dice ser.
     *
     * @return list<string>
     */
    private function leerFotos(string $origen): array
    {
        $fuentes = [];

        foreach (glob($origen . '/*') ?: [] as $ruta) {
            if (! is_file($ruta)) {
                continue;
            }

            $info = @getimagesize($ruta);

            if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                $this->warn('  Ignorada (no es una imagen): ' . basename($ruta));
                continue;
            }

            $fuentes[] = $ruta;
        }

        sort($fuentes);

        return $fuentes;
    }

    /**
     * Redimensiona, recomprime y guarda en el disco público.
     *
     * El nombre sale del contenido del archivo, no de un azar: así volver a
     * lanzar el comando reutiliza las mismas rutas en vez de llenar el disco
     * de copias.
     *
     * @param  list<string>  $fuentes
     * @return list<string>
     */
    private function procesar(array $fuentes): array
    {
        $rutas = [];
        $barra = $this->output->createProgressBar(count($fuentes));
        $barra->start();

        foreach ($fuentes as $ruta) {
            $destino = self::DESTINO . '/' . substr(hash_file('sha256', $ruta), 0, 16) . '.jpg';

            if (! Storage::disk('public')->exists($destino)) {
                $jpeg = $this->recomprimir($ruta);

                if ($jpeg === null) {
                    $barra->advance();
                    continue;
                }

                Storage::disk('public')->put($destino, $jpeg);
            }

            $rutas[] = $destino;
            $barra->advance();
        }

        $barra->finish();
        $this->newLine(2);

        return $rutas;
    }

    /** Devuelve el JPEG ya redimensionado, o null si GD no pudo con él. */
    private function recomprimir(string $ruta): ?string
    {
        $imagen = @imagecreatefromstring((string) file_get_contents($ruta));

        if ($imagen === false) {
            $this->warn('  GD no pudo leer: ' . basename($ruta));

            return null;
        }

        if (imagesx($imagen) > self::ANCHO_MAX) {
            $reducida = imagescale($imagen, self::ANCHO_MAX);

            if ($reducida !== false) {
                imagedestroy($imagen);
                $imagen = $reducida;
            }
        }

        ob_start();
        imagejpeg($imagen, null, self::CALIDAD);
        imagedestroy($imagen);

        return (string) ob_get_clean();
    }

    /**
     * Asigna las fotos a los alojamientos.
     *
     * El desplazamiento por índice evita que las veinte publicaciones salgan
     * con la misma foto de portada, que es lo que delata una demo.
     *
     * @param  list<string>  $fotos
     */
    private function repartir(array $fotos): int
    {
        $por = max(1, (int) $this->option('por'));

        $consulta = Alojamiento::query();

        if (! $this->option('forzar')) {
            $consulta->where(fn ($q) => $q->whereNull('photos')->orWhere('photos', '[]'));
        }

        $alojamientos = $consulta->orderBy('id')->get();

        if ($alojamientos->isEmpty()) {
            // Sin esta distinción el comando dice «ya tienen fotos» cuando
            // en realidad la tabla está vacía, y uno se queda buscando qué
            // pasó con las fotos que acaba de subir.
            if (Alojamiento::count() === 0) {
                $this->warn('No hay ningún alojamiento al que ponerle fotos.');
                $this->line('  Carga primero los de ejemplo:');
                $this->line('    php artisan db:seed --class=MonteriaSeeder');

                return self::FAILURE;
            }

            $this->info('Todos los alojamientos ya tienen fotos. Usa --forzar para reemplazarlas.');

            return self::SUCCESS;
        }

        $total = count($fotos);

        foreach ($alojamientos->values() as $i => $alojamiento) {
            $suyas = [];

            for ($n = 0; $n < min($por, $total); $n++) {
                $suyas[] = $fotos[(($i * $por) + $n) % $total];
            }

            $alojamiento->forceFill([
                'photos'     => $suyas,
                'cover_path' => $suyas[0],
            ])->save();
        }

        $this->newLine();
        $this->info(sprintf(
            '%d alojamientos actualizados con %d fotos cada uno.',
            $alojamientos->count(),
            min($por, $total),
        ));

        return self::SUCCESS;
    }
}
