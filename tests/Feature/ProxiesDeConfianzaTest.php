<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustProxies;
use Tests\TestCase;

class ProxiesDeConfianzaTest extends TestCase
{
    private function proxiesDe(?string $valor): array|string|null
    {
        // Se ajusta la configuración, no la variable de entorno: así el test
        // no depende del .env que tenga cada quien en su máquina.
        config(['app.trusted_proxies' => $valor]);

        $middleware = new TrustProxies;

        // La propiedad es protected; se lee por reflexión porque lo que
        // importa es el valor que acaba viendo Symfony.
        $propiedad = new \ReflectionProperty($middleware, 'proxies');
        $propiedad->setAccessible(true);

        return $propiedad->getValue($middleware);
    }

    public function test_sin_la_variable_no_confia_en_ningun_proxy(): void
    {
        // Lo seguro por defecto: confiar en las cabeceras X-Forwarded-* de
        // cualquiera permite falsear la IP que guarda la auditoría.
        $this->assertNull($this->proxiesDe(null));
        $this->assertNull($this->proxiesDe(''));
    }

    public function test_el_comodin_confia_en_todos(): void
    {
        $this->assertSame('*', $this->proxiesDe('*'));
    }

    public function test_acepta_una_lista_de_direcciones(): void
    {
        $this->assertSame(
            ['10.0.0.1', '10.0.0.2'],
            $this->proxiesDe('10.0.0.1, 10.0.0.2')
        );
    }

    public function test_el_valor_viene_de_la_configuracion_no_de_env(): void
    {
        // env() dentro de un middleware devuelve null en cuanto alguien
        // ejecuta «php artisan config:cache». Este test fija que el valor se
        // toma de la configuración, que sí sobrevive al caché.
        config(['app.trusted_proxies' => '*']);
        putenv('TRUSTED_PROXIES=10.9.9.9');

        $middleware = new TrustProxies;
        $propiedad = new \ReflectionProperty($middleware, 'proxies');
        $propiedad->setAccessible(true);

        $this->assertSame('*', $propiedad->getValue($middleware));

        putenv('TRUSTED_PROXIES');
    }
}
