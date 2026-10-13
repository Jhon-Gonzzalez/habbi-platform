<?php

namespace Tests\Feature;

use App\Support\ArchivoEnv;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class ConfigurarCorreoTest extends TestCase
{
    private string $envReal;

    private string $copia;

    /**
     * El comando ESCRIBE en el .env de verdad. Se guarda una copia y se
     * restaura siempre, para que correr los tests no deje la máquina de
     * nadie configurada con datos de prueba.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->envReal = base_path('.env');
        $this->copia   = $this->envReal . '.test-backup';

        if (is_file($this->envReal)) {
            copy($this->envReal, $this->copia);
        }
    }

    protected function tearDown(): void
    {
        if (is_file($this->copia)) {
            copy($this->copia, $this->envReal);
            unlink($this->copia);
        }

        parent::tearDown();
    }

    private function env(): ArchivoEnv
    {
        return new ArchivoEnv($this->envReal);
    }

    /** Ejecuta el comando capturando su salida (sin preguntas interactivas). */
    private function salidaDe(array $args): string
    {
        $this->withoutMockingConsoleOutput();

        $buffer = new BufferedOutput();
        Artisan::call('habbi:correo', $args, $buffer);

        return $buffer->fetch();
    }

    /*
     * Las pruebas interactivas apuntan a 127.0.0.1:1, donde no escucha
     * nadie: el envío falla al instante, sin DNS ni salida a internet. Si
     * apuntaran a smtp.gmail.com de verdad, el test dependería de la red y
     * de Google, y en CI tardaría o fallaría sin motivo.
     */
    private const HOST_MUERTO = ['--host' => '127.0.0.1', '--port' => '1'];

    public function test_la_opcion_log_deja_el_correo_en_modo_local(): void
    {
        $salida = $this->salidaDe(['--log' => true]);

        $this->assertSame('log', $this->env()->leer('MAIL_MAILER'));

        // Y deja un remitente: sin él, ni el driver "log" puede construir
        // el mensaje.
        $this->assertNotEmpty($this->env()->leer('MAIL_FROM_ADDRESS'));
        $this->assertStringContainsString('laravel.log', $salida);
    }

    public function test_en_modo_log_la_prueba_avisa_de_que_no_envia_nada(): void
    {
        $this->salidaDe(['--log' => true]);

        $this->assertStringContainsString('no envía nada', $this->salidaDe(['--probar' => true]));
    }

    public function test_la_prueba_se_manda_a_la_direccion_indicada(): void
    {
        $this->salidaDe(['--log' => true]);

        $salida = $this->salidaDe(['--probar' => true, '--a' => 'destino@ejemplo.test']);

        $this->assertStringContainsString('destino@ejemplo.test', $salida);
    }

    public function test_guarda_la_contrasena_sin_los_espacios_que_muestra_google(): void
    {
        // Google la enseña en grupos de cuatro y casi todo el mundo la pega
        // tal cual. Con espacios, SMTP la rechaza.
        $this->artisan('habbi:correo', self::HOST_MUERTO)
            ->expectsQuestion('Tu dirección de Gmail', 'jhon@gmail.com')
            ->expectsQuestion('Contraseña de aplicación (16 letras, no se verá al escribir)', 'abcd efgh ijkl mnop')
            ->assertFailed();   // falla el envío, pero la config ya se guardó

        $this->assertSame('abcdefghijklmnop', $this->env()->leer('MAIL_PASSWORD'));
        $this->assertSame('jhon@gmail.com', $this->env()->leer('MAIL_USERNAME'));
        $this->assertSame('smtp', $this->env()->leer('MAIL_MAILER'));
    }

    public function test_el_remitente_queda_igual_al_usuario(): void
    {
        $this->artisan('habbi:correo', self::HOST_MUERTO)
            ->expectsQuestion('Tu dirección de Gmail', 'jhon@gmail.com')
            ->expectsQuestion('Contraseña de aplicación (16 letras, no se verá al escribir)', 'abcdefghijklmnop')
            ->assertFailed();

        $this->assertSame('jhon@gmail.com', $this->env()->leer('MAIL_FROM_ADDRESS'));
    }

    public function test_rechaza_un_correo_mal_escrito_sin_tocar_el_env(): void
    {
        $antes = file_get_contents($this->envReal);

        $this->artisan('habbi:correo')
            ->expectsQuestion('Tu dirección de Gmail', 'esto-no-es-un-correo')
            ->assertFailed();

        $this->assertSame($antes, file_get_contents($this->envReal),
            'Con un correo inválido no se debe tocar el .env.');
    }

    public function test_un_fallo_de_conexion_se_explica_en_castellano(): void
    {
        // El mensaje de Symfony es en inglés y no dice qué hacer. El comando
        // lo traduce a algo accionable; esto comprueba que esa traducción
        // sale de verdad y no se queda en el código sin usarse nunca.
        $this->artisan('habbi:correo', self::HOST_MUERTO)
            ->expectsQuestion('Tu dirección de Gmail', 'jhon@gmail.com')
            ->expectsQuestion('Contraseña de aplicación (16 letras, no se verá al escribir)', 'abcdefghijklmnop')
            ->expectsOutputToContain('No se pudo enviar')
            ->expectsOutputToContain('No se pudo conectar con el servidor')
            ->assertFailed();
    }
}
