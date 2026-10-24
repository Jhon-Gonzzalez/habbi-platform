<?php

namespace Tests\Unit;

use App\Support\ArchivoEnv;
use PHPUnit\Framework\TestCase;

class ArchivoEnvTest extends TestCase
{
    private string $ruta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ruta = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($this->ruta, "APP_NAME=HABBI\nMAIL_MAILER=log\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->ruta);
        parent::tearDown();
    }

    private function env(): ArchivoEnv
    {
        return new ArchivoEnv($this->ruta);
    }

    public function test_reemplaza_una_clave_existente_sin_duplicarla(): void
    {
        $this->env()->escribir('MAIL_MAILER', 'smtp');

        $this->assertSame('smtp', $this->env()->leer('MAIL_MAILER'));
        $this->assertSame(1, substr_count(file_get_contents($this->ruta), 'MAIL_MAILER='));
    }

    public function test_anade_una_clave_que_no_estaba(): void
    {
        $this->env()->escribir('MAIL_PORT', '587');

        $this->assertSame('587', $this->env()->leer('MAIL_PORT'));
        $this->assertSame('HABBI', $this->env()->leer('APP_NAME'));
    }

    /**
     * Una contraseña de aplicación puede traer cualquier cosa. Si no se
     * escapan, el .env queda corrupto y la aplicación deja de arrancar.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('valoresDificiles')]
    public function test_guarda_valores_con_caracteres_que_rompen_el_env(string $valor): void
    {
        $this->env()->escribir('MAIL_PASSWORD', $valor);

        $this->assertSame($valor, $this->env()->leer('MAIL_PASSWORD'),
            "No se recuperó igual el valor: {$valor}");
    }

    public static function valoresDificiles(): array
    {
        return [
            'con espacios'    => ['abcd efgh ijkl mnop'],
            'con almohadilla' => ['clave#secreta'],
            'con dolar'       => ['cla$ve'],
            'con comillas'    => ['cla"ve'],
            'con barra'       => ['cla\\ve'],
            'con todo'        => ['a b#c$d"e\\f'],
            'vacio'           => [''],
        ];
    }

    public function test_rechaza_una_clave_con_formato_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->env()->escribir('mail mailer; rm -rf /', 'x');
    }

    public function test_leer_una_clave_que_no_existe_da_null(): void
    {
        $this->assertNull($this->env()->leer('NO_EXISTE'));
    }
}
