<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProxiesDeConfianzaTest extends TestCase
{
    // La prueba del túnel pide una página de verdad, así que hace falta
    // la base. Las demás solo leen configuración.
    use RefreshDatabase;

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

    public function test_detras_de_un_tunel_los_enlaces_salen_en_https(): void
    {
        /*
         * El caso real de deploy/tunel.sh, y el que rompe la página.
         *
         * El túnel habla https con el visitante y http con la máquina, así
         * que sin confiar en el proxy Laravel escribe los enlaces del CSS
         * y del JS como http://. El navegador los bloquea por «contenido
         * mixto» y el sitio sale sin estilos: un fallo que solo aparece
         * al publicar, nunca en local.
         */
        config(['app.trusted_proxies' => '*']);

        $respuesta = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host'  => 'abc.trycloudflare.com',
        ])->get('/alojamientos')->assertOk();

        $this->assertStringContainsString('https://', $respuesta->getContent());
        $this->assertStringNotContainsString(
            'href="http://',
            $respuesta->getContent(),
            'Hay enlaces en http: el navegador los bloquearía detrás del túnel.',
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
