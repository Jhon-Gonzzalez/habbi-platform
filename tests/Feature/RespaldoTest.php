<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Services\RespaldoBaseDeDatos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use Tests\TestCase;

/**
 * Copias de seguridad de la base de datos (RNF-3).
 *
 * Las pruebas trabajan sobre una base SQLite en un fichero temporal, no
 * sobre la de los tests (que está en memoria y no se puede copiar). Lo
 * que se comprueba es lo que de verdad importa de un respaldo: que el
 * archivo se pueda abrir y tenga los datos dentro, y que la rotación no
 * se lleve por delante lo que aún sirve.
 */
class RespaldoTest extends TestCase
{
    use RefreshDatabase;

    private string $carpeta;

    private string $baseDeDatos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carpeta     = storage_path('framework/testing/respaldos-' . uniqid());
        $this->baseDeDatos = storage_path('framework/testing/base-' . uniqid() . '.sqlite');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->carpeta);
        File::delete($this->baseDeDatos);

        parent::tearDown();
    }

    /**
     * Crea una base SQLite de verdad, en disco, con una tabla dentro, y
     * apunta la conexión por defecto a ella.
     */
    private function baseEnDisco(): void
    {
        $pdo = new PDO('sqlite:' . $this->baseDeDatos);
        $pdo->exec('CREATE TABLE alojamientos (id INTEGER PRIMARY KEY, title TEXT)');
        $pdo->exec("INSERT INTO alojamientos (title) VALUES ('Habitación en La Granja')");
        $pdo = null;

        Config::set('database.default', 'respaldo_pruebas');
        Config::set('database.connections.respaldo_pruebas', [
            'driver'   => 'sqlite',
            'database' => $this->baseDeDatos,
            'prefix'   => '',
        ]);
    }

    /**
     * Como la anterior, pero con el esquema real migrado dentro.
     *
     * Hace falta cuando la prueba toca la auditoría: al cambiar la
     * conexión por defecto, el Auditor escribe en ESTA base, y si no
     * tiene la tabla la línea se pierde en silencio (el Auditor no lanza
     * excepciones a propósito) y la prueba pasaría sin comprobar nada.
     */
    private function baseMigrada(): void
    {
        Config::set('database.default', 'respaldo_pruebas');
        Config::set('database.connections.respaldo_pruebas', [
            'driver'   => 'sqlite',
            'database' => $this->baseDeDatos,
            'prefix'   => '',
        ]);

        File::put($this->baseDeDatos, '');

        $this->artisan('migrate', [
            '--database' => 'respaldo_pruebas',
            '--force'    => true,
        ])->assertSuccessful();
    }

    private function servicio(): RespaldoBaseDeDatos
    {
        return new RespaldoBaseDeDatos($this->carpeta);
    }

    /* ===================== Crear ===================== */

    public function test_el_respaldo_se_puede_abrir_y_tiene_los_datos_dentro(): void
    {
        $this->baseEnDisco();

        $archivo = $this->servicio()->crear();

        $this->assertFileExists($archivo);

        // Esto es la prueba de verdad: un respaldo que no se puede abrir
        // no es un respaldo, es un fichero.
        $copia = new PDO('sqlite:' . $archivo);

        $this->assertSame(
            'Habitación en La Granja',
            $copia->query('SELECT title FROM alojamientos')->fetchColumn(),
        );
    }

    public function test_la_carpeta_y_el_archivo_no_quedan_legibles_para_cualquiera(): void
    {
        $this->baseEnDisco();

        $archivo = $this->servicio()->crear();

        // El volcado lleva correos, teléfonos y contraseñas cifradas.
        $this->assertSame('0700', substr(sprintf('%o', fileperms($this->carpeta)), -4));
        $this->assertSame('0600', substr(sprintf('%o', fileperms($archivo)), -4));
    }

    public function test_el_respaldo_queda_fuera_de_la_carpeta_publica(): void
    {
        // Por defecto, sin pasarle carpeta: lo que se usa en producción.
        $destino = (new RespaldoBaseDeDatos())->carpeta();

        $this->assertStringNotContainsString(public_path(), $destino);
        $this->assertStringStartsWith(storage_path(), $destino);
    }

    public function test_dos_respaldos_seguidos_no_se_pisan(): void
    {
        $this->baseEnDisco();

        $servicio = $this->servicio();

        $primero = $servicio->crear(now());
        $segundo = $servicio->crear(now()->addSecond());

        $this->assertNotSame($primero, $segundo);
        $this->assertCount(2, $servicio->archivos());
    }

    public function test_una_base_en_memoria_avisa_en_vez_de_crear_un_archivo_vacio(): void
    {
        Config::set('database.default', 'memoria');
        Config::set('database.connections.memoria', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no hay nada que respaldar');

        $this->servicio()->crear();
    }

    public function test_un_motor_no_soportado_avisa_claramente(): void
    {
        Config::set('database.default', 'raro');
        Config::set('database.connections.raro', ['driver' => 'pgsql']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('pgsql');

        $this->servicio()->crear();
    }

    /* ===================== Rotar ===================== */

    public function test_la_rotacion_borra_lo_viejo_y_deja_lo_reciente(): void
    {
        File::makeDirectory($this->carpeta, 0700, true);

        $viejo    = $this->carpeta . '/habbi_2020-01-01_030000.sqlite';
        $reciente = $this->carpeta . '/habbi_2026-01-01_030000.sqlite';

        File::put($viejo, 'x');
        File::put($reciente, 'x');
        touch($viejo, now()->subDays(40)->timestamp);
        touch($reciente, now()->subDay()->timestamp);

        $borrados = $this->servicio()->rotar(14);

        $this->assertSame([basename($viejo)], $borrados);
        $this->assertFileDoesNotExist($viejo);
        $this->assertFileExists($reciente);
    }

    public function test_la_rotacion_no_toca_nada_que_no_sea_un_respaldo(): void
    {
        File::makeDirectory($this->carpeta, 0700, true);

        $ajeno = $this->carpeta . '/notas-del-administrador.txt';
        File::put($ajeno, 'no me borres');
        touch($ajeno, now()->subYears(3)->timestamp);

        $this->servicio()->rotar(14);

        $this->assertFileExists($ajeno);
    }

    public function test_con_cero_dias_no_se_borra_nada(): void
    {
        // Un 0 en la configuración borraría el respaldo recién hecho. Más
        // vale no rotar que quedarse sin copias por un dato mal puesto.
        File::makeDirectory($this->carpeta, 0700, true);

        $archivo = $this->carpeta . '/habbi_2020-01-01_030000.sqlite';
        File::put($archivo, 'x');
        touch($archivo, now()->subYears(3)->timestamp);

        $this->assertSame([], $this->servicio()->rotar(0));
        $this->assertFileExists($archivo);
    }

    public function test_los_respaldos_se_listan_del_mas_nuevo_al_mas_viejo(): void
    {
        File::makeDirectory($this->carpeta, 0700, true);

        foreach ([3, 1, 2] as $diasAtras) {
            $ruta = $this->carpeta . "/habbi_2026-01-0{$diasAtras}_030000.sqlite";
            File::put($ruta, 'x');
            touch($ruta, now()->subDays($diasAtras)->timestamp);
        }

        $nombres = array_map('basename', $this->servicio()->archivos());

        $this->assertSame([
            'habbi_2026-01-01_030000.sqlite',
            'habbi_2026-01-02_030000.sqlite',
            'habbi_2026-01-03_030000.sqlite',
        ], $nombres);
    }

    /* ===================== El comando ===================== */

    public function test_el_comando_respalda_rota_y_lo_anota_en_la_auditoria(): void
    {
        $this->baseMigrada();
        $this->app->instance(RespaldoBaseDeDatos::class, $this->servicio());

        File::makeDirectory($this->carpeta, 0700, true);
        $caducado = $this->carpeta . '/habbi_2019-01-01_030000.sqlite';
        File::put($caducado, 'x');
        touch($caducado, now()->subDays(99)->timestamp);

        $this->artisan('habbi:respaldar', ['--dias' => 14])
            ->expectsOutputToContain('Respaldo creado')
            ->assertSuccessful();

        $this->assertFileDoesNotExist($caducado);

        $linea = Auditoria::where('accion', Auditoria::RESPALDO_CREADO)->first();

        $this->assertNotNull($linea);
        // Lo ejecuta el programador de tareas, sin nadie con sesión abierta.
        $this->assertSame('sistema', $linea->actor_nombre);
        $this->assertGreaterThan(0, $linea->datos['bytes']);
    }

    public function test_el_comando_falla_con_codigo_de_error_si_no_puede_respaldar(): void
    {
        $this->app->instance(RespaldoBaseDeDatos::class, $this->servicio());

        Config::set('database.default', 'raro');
        Config::set('database.connections.raro', ['driver' => 'informix']);

        $this->artisan('habbi:respaldar')->assertFailed();

        $this->assertSame([], $this->servicio()->archivos());
    }

    public function test_el_comando_listar_dice_que_no_hay_nada_cuando_no_hay_nada(): void
    {
        $this->app->instance(RespaldoBaseDeDatos::class, $this->servicio());

        $this->artisan('habbi:respaldar', ['--listar' => true])
            ->expectsOutputToContain('No hay ningún respaldo')
            ->assertSuccessful();
    }

    /* ===================== El aviso del panel ===================== */

    public function test_en_produccion_el_panel_avisa_si_no_hay_respaldos(): void
    {
        $this->app->instance(RespaldoBaseDeDatos::class, $this->servicio());
        $this->app['env'] = 'production';

        $this->actingAs(\App\Models\User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('No hay ninguna copia de seguridad');
    }

    public function test_en_produccion_el_panel_avisa_si_el_respaldo_es_viejo(): void
    {
        $this->app->instance(RespaldoBaseDeDatos::class, $this->servicio());
        $this->app['env'] = 'production';

        File::makeDirectory($this->carpeta, 0700, true);
        $viejo = $this->carpeta . '/habbi_2026-01-01_030000.sqlite';
        File::put($viejo, 'x');
        touch($viejo, now()->subDays(5)->timestamp);

        $this->actingAs(\App\Models\User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('La última copia de seguridad es del');
    }

    public function test_con_un_respaldo_reciente_el_panel_no_alarma(): void
    {
        $this->app->instance(RespaldoBaseDeDatos::class, $this->servicio());
        $this->app['env'] = 'production';

        File::makeDirectory($this->carpeta, 0700, true);
        $reciente = $this->carpeta . '/habbi_2026-01-01_030000.sqlite';
        File::put($reciente, 'x');
        touch($reciente, now()->subHours(6)->timestamp);

        $this->actingAs(\App\Models\User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee('No hay ninguna copia de seguridad')
            ->assertSee('Última copia de seguridad:');
    }

    public function test_en_local_el_panel_no_avisa_de_los_respaldos(): void
    {
        // En desarrollo nadie tiene el cron puesto: el aviso saldría en
        // cada carga y se acabaría ignorando también en producción.
        $this->app->instance(RespaldoBaseDeDatos::class, $this->servicio());

        $this->actingAs(\App\Models\User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee('No hay ninguna copia de seguridad');
    }

    public function test_la_tarea_diaria_esta_programada(): void
    {
        /*
         * Que el comando exista no sirve de nada si nadie lo llama. Esto
         * comprueba que está en el programador; que el cron del servidor
         * despierte al programador ya es cosa del despliegue, y está
         * escrito en docs/respaldos.md.
         */
        $programadas = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->map(fn ($e) => $e->command)
            ->filter();

        $this->assertTrue(
            $programadas->contains(fn ($c) => str_contains($c, 'habbi:respaldar')),
            'La copia de seguridad diaria no está en el programador de tareas.',
        );
    }
}
