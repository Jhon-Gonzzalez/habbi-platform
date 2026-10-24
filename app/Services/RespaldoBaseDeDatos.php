<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Copias de seguridad de la base de datos (RNF-3).
 *
 * Soporta las dos conexiones que usa el proyecto: SQLite en desarrollo
 * (copiar el fichero basta) y MySQL en el servidor (mysqldump). Cualquier
 * otro motor se rechaza con un aviso claro en vez de generar un archivo
 * vacío que nadie mira hasta el día que hace falta restaurar.
 *
 * Lo que NO hace, a propósito:
 *
 *   · No sube nada a ninguna nube. Un respaldo en el mismo disco protege
 *     de un borrado por error, no de que el disco se queme. Lo segundo
 *     exige una cuenta de almacenamiento externo, que es una decisión del
 *     que despliega y no del código. En docs/respaldos.md está escrito.
 *   · No cifra el archivo. Contiene datos personales, así que la carpeta
 *     se crea fuera de public/ y con permisos 0700.
 */
class RespaldoBaseDeDatos
{
    /**
     * @param  string|null  $carpeta   Dónde guardar. Por defecto, storage/app/respaldos.
     * @param  string|null  $conexion  Qué base respaldar. Por defecto, la de la aplicación.
     *
     * La conexión es un parámetro y no una lectura directa de la
     * configuración para que se pueda respaldar una base distinta de la
     * que la aplicación está usando. Eso hace falta de verdad en las
     * pruebas: cambiar la conexión por defecto a mitad de un test
     * arrastra con ella a todo lo demás, y en Laravel 12 eso llega a
     * corromper la base en memoria.
     */
    public function __construct(
        private readonly ?string $carpeta = null,
        private readonly ?string $conexion = null,
    ) {
    }

    private function conexion(): string
    {
        return $this->conexion ?? config('database.default');
    }

    /** Dónde se guardan. Fuera de public/: el volcado lleva datos personales. */
    public function carpeta(): string
    {
        return $this->carpeta ?? storage_path('app/respaldos');
    }

    /**
     * Genera un respaldo y devuelve la ruta del archivo creado.
     *
     * @throws RuntimeException si el motor no está soportado o el volcado falla
     */
    public function crear(?Carbon $momento = null): string
    {
        $carpeta = $this->carpeta();

        if (! File::isDirectory($carpeta)) {
            File::makeDirectory($carpeta, 0700, true);
        }

        $conexion = $this->conexion();
        $motor    = config("database.connections.{$conexion}.driver");
        $sello    = ($momento ?? now())->format('Y-m-d_His');

        return match ($motor) {
            'sqlite' => $this->desdeSqlite($conexion, $carpeta, $sello),
            'mysql', 'mariadb' => $this->desdeMysql($conexion, $carpeta, $sello),
            default  => throw new RuntimeException(
                "No sé respaldar una base de datos «{$motor}». "
                . 'Están cubiertos sqlite y mysql, que son los que usa el proyecto.'
            ),
        };
    }

    /** En SQLite la base es un solo fichero: copiarlo es el respaldo. */
    private function desdeSqlite(string $conexion, string $carpeta, string $sello): string
    {
        $origen = config("database.connections.{$conexion}.database");

        if ($origen === ':memory:') {
            throw new RuntimeException(
                'La base de datos está en memoria: no hay nada que respaldar.'
            );
        }

        if (! File::exists($origen)) {
            throw new RuntimeException("No encuentro el fichero de la base de datos: {$origen}");
        }

        /*
         * Un WAL pendiente deja los últimos cambios fuera del .sqlite, así
         * que la copia saldría desactualizada. Esto los vuelca antes.
         */
        DB::connection($conexion)->statement('PRAGMA wal_checkpoint(TRUNCATE)');

        $destino = "{$carpeta}/habbi_{$sello}.sqlite";

        if (! File::copy($origen, $destino)) {
            throw new RuntimeException("No pude copiar la base de datos a {$destino}");
        }

        File::chmod($destino, 0600);

        return $destino;
    }

    /** En MySQL se usa mysqldump y se comprime con gzip. */
    private function desdeMysql(string $conexion, string $carpeta, string $sello): string
    {
        $cfg     = config("database.connections.{$conexion}");
        $destino = "{$carpeta}/habbi_{$sello}.sql.gz";

        /*
         * La contraseña NO va en los argumentos: cualquiera con acceso al
         * servidor la vería en `ps aux` mientras el volcado corre. Va en un
         * fichero temporal con permisos 0600 que mysqldump lee con
         * --defaults-extra-file, y que se borra al terminar pase lo que pase.
         */
        $credenciales = tempnam(sys_get_temp_dir(), 'habbi-dump-');

        if ($credenciales === false) {
            throw new RuntimeException('No pude crear el fichero temporal de credenciales.');
        }

        try {
            File::chmod($credenciales, 0600);
            File::put($credenciales, implode("\n", [
                '[client]',
                'user = ' . ($cfg['username'] ?? ''),
                'password = "' . str_replace('"', '\\"', (string) ($cfg['password'] ?? '')) . '"',
                'host = ' . ($cfg['host'] ?? '127.0.0.1'),
                'port = ' . ($cfg['port'] ?? 3306),
                '',
            ]));

            $proceso = Process::fromShellCommandline(
                'mysqldump --defaults-extra-file=$CREDENCIALES '
                . '--single-transaction --quick --routines --events '
                . '--no-tablespaces $BASE | gzip > $DESTINO'
            );

            $proceso->setTimeout(600);
            $proceso->run(null, [
                'CREDENCIALES' => $credenciales,
                'BASE'         => $cfg['database'],
                'DESTINO'      => $destino,
            ]);

            if (! $proceso->isSuccessful()) {
                // El archivo a medias es peor que ninguno: engaña.
                File::delete($destino);

                throw new RuntimeException(
                    'mysqldump falló: ' . trim($proceso->getErrorOutput() ?: $proceso->getOutput())
                );
            }
        } finally {
            File::delete($credenciales);
        }

        File::chmod($destino, 0600);

        return $destino;
    }

    /**
     * Borra los respaldos con más de $dias días y devuelve los eliminados.
     *
     * @return list<string>
     */
    public function rotar(int $dias): array
    {
        $carpeta = $this->carpeta();

        if (! File::isDirectory($carpeta) || $dias < 1) {
            return [];
        }

        $limite   = now()->subDays($dias);
        $borrados = [];

        foreach ($this->archivos() as $ruta) {
            if (Carbon::createFromTimestamp(File::lastModified($ruta))->lt($limite)) {
                File::delete($ruta);
                $borrados[] = basename($ruta);
            }
        }

        return $borrados;
    }

    /**
     * Los respaldos que hay ahora, del más reciente al más antiguo.
     *
     * @return list<string>
     */
    public function archivos(): array
    {
        $carpeta = $this->carpeta();

        if (! File::isDirectory($carpeta)) {
            return [];
        }

        $archivos = array_merge(
            File::glob("{$carpeta}/habbi_*.sqlite") ?: [],
            File::glob("{$carpeta}/habbi_*.sql.gz") ?: [],
        );

        usort($archivos, fn ($a, $b) => File::lastModified($b) <=> File::lastModified($a));

        return array_values($archivos);
    }
}
