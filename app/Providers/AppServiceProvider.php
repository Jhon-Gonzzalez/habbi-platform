<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
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

        // Paginación con las clases del sistema de diseño en lugar de Tailwind.
        Paginator::defaultView('vendor.pagination.habbi');
        Paginator::defaultSimpleView('vendor.pagination.habbi');

        // @plural('huésped', $n) → «huéspedes», con reglas del español.
        Blade::directive('plural', fn (string $args) => "<?php echo \App\Support\Texto::plural({$args}); ?>");
    }
}
