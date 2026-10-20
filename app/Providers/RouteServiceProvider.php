<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/mi-cuenta';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->limitesContraElAbuso();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Límites de velocidad de las acciones que se pueden abusar.
     *
     * Los números están puestos muy por encima de lo que hace una persona
     * de verdad: un estudiante buscando cuarto mira quince o veinte
     * teléfonos en una tarde, no doscientos. La idea no es estorbar a
     * nadie, es que un script no pueda vaciar la base de datos ni llenar
     * la plataforma de reseñas falsas en un minuto.
     *
     * Los que no tienen sesión se cuentan por IP; los demás por usuario,
     * para que cambiar de red no sirva de nada.
     */
    private function limitesContraElAbuso(): void
    {
        // Lo más valioso que hay aquí: los datos de contacto.
        //
        // Las dos reglas llevan prefijo distinto en la clave a propósito. Si
        // compartieran clave, Laravel usaría el MISMO contador para las dos y
        // cada petición descontaría dos veces: el límite real sería la mitad
        // del escrito. Se descubrió probándolo: cortaba en la 13, no en la 25.
        RateLimiter::for('telefonos', fn (Request $r) => [
            Limit::perHour(25)->by('tel-hora:' . ($r->user()?->id ?: $r->ip())),
            Limit::perDay(80)->by('tel-dia:' . ($r->user()?->id ?: $r->ip())),
        ]);

        // Mensajes: un acosador no puede inundar a nadie, y una persona
        // normal escribiendo a varios arrendadores no lo nota.
        RateLimiter::for('mensajes', fn (Request $r) => Limit::perMinute(20)->by($r->user()?->id ?: $r->ip()));

        // Crear cuentas en masa es el primer paso de casi todo lo demás.
        RateLimiter::for('registro', fn (Request $r) => Limit::perHour(5)->by($r->ip()));

        // Una reseña afecta la reputación de alguien.
        RateLimiter::for('resenas', fn (Request $r) => Limit::perHour(10)->by($r->user()?->id ?: $r->ip()));

        // Reportar en masa podría usarse para hostigar a un competidor.
        RateLimiter::for('reportes', fn (Request $r) => Limit::perHour(5)->by($r->user()?->id ?: $r->ip()));

        // Publicar es caro para el servidor: hasta ocho fotos por anuncio.
        RateLimiter::for('publicar', fn (Request $r) => Limit::perDay(15)->by($r->user()?->id ?: $r->ip()));
    }
}
