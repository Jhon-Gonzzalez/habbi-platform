<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // MySQL < 5.7.7 / MariaDB < 10.2.2 limitan el tamaño de los índices.
        Schema::defaultStringLength(191);

        // Si el sitio vive en https, todos los enlaces y formularios que
        // genere Laravel deben serlo también. Sin esto, basta con que una
        // página se sirva por http para que el navegador bloquee el CSS o
        // avise de "contenido mixto".
        //
        // Se mira APP_URL y no el entorno: así un despliegue de pruebas sin
        // certificado sigue funcionando, en vez de romperse al forzar un
        // https que ese servidor no sabe responder.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Paginación con las clases del sistema de diseño en lugar de Tailwind.
        Paginator::defaultView('vendor.pagination.habbi');
        Paginator::defaultSimpleView('vendor.pagination.habbi');

        // @plural('huésped', $n) → «huéspedes», con reglas del español.
        Blade::directive('plural', fn (string $args) => "<?php echo \App\Support\Texto::plural({$args}); ?>");
    }
}
