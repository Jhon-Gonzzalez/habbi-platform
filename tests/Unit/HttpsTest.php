<?php

namespace Tests\Unit;

use Tests\TestCase;

class HttpsTest extends TestCase
{
    public function test_con_app_url_en_https_los_enlaces_salen_en_https(): void
    {
        // No basta con mirar el entorno: lo que decide es la URL del sitio.
        config(['app.url' => 'https://habbi.co']);
        $this->refreshApplicationWithUrl();

        $this->assertStringStartsWith('https://', route('index'));
    }

    public function test_con_app_url_en_http_no_se_fuerza_nada(): void
    {
        // Un despliegue de pruebas sin certificado debe seguir funcionando.
        config(['app.url' => 'http://localhost:8000']);
        $this->refreshApplicationWithUrl();

        $this->assertStringStartsWith('http://', route('index'));
    }

    /** Vuelve a arrancar el proveedor con la configuración recién puesta. */
    private function refreshApplicationWithUrl(): void
    {
        $this->app->forgetInstance('url');
        (new \App\Providers\AppServiceProvider($this->app))->boot();
        url()->forceRootUrl(config('app.url'));
    }
}
