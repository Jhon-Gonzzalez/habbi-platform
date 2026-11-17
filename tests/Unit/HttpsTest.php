<?php

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

class HttpsTest extends TestCase
{
    public function test_con_app_url_en_https_los_enlaces_salen_en_https(): void
    {
        // No basta con mirar el entorno: lo que decide es la URL del sitio.
        config(['app.url' => 'https://habbi.co']);
        $this->rearrancarProveedor();

        $this->assertStringStartsWith('https://', route('index'));
    }

    public function test_con_app_url_en_http_no_se_fuerza_nada(): void
    {
        // Un despliegue de pruebas sin certificado debe seguir funcionando.
        config(['app.url' => 'http://localhost:8000']);
        $this->rearrancarProveedor();

        $this->assertStringStartsWith('http://', route('index'));
    }

    /**
     * Vuelve a arrancar el proveedor con la configuración recién puesta.
     *
     * OJO: aquí NO se puede hacer $this->app->forgetInstance('url').
     *
     * Al resolver «url» de nuevo, Laravel ejecuta por dentro
     * $app->instance('routes', $routes), y eso dispara los callbacks de
     * rebinding. Uno de esos callbacks hace $app['url']->setRoutes(...),
     * que vuelve a resolver «url» cuando todavía se está construyendo:
     * resolver → fijar routes → disparar → resolver… hasta que PHP corta
     * con «Maximum call stack size reached. Infinite recursion?».
     *
     * No hace falta olvidarlo: forceScheme() y forceRootUrl() modifican el
     * generador que ya existe, y cada método de prueba arranca con una
     * aplicación nueva, así que no se filtra nada de una prueba a otra.
     */
    private function rearrancarProveedor(): void
    {
        (new AppServiceProvider($this->app))->boot();

        url()->forceRootUrl(config('app.url'));
    }
}
