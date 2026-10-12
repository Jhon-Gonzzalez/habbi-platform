<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Proxies de confianza.
     *
     * Se toman de config('app.trusted_proxies'), donde está explicado el
     * porqué y el riesgo. Se lee de la configuración y NO con env(): dentro
     * de un middleware, env() devuelve null en cuanto alguien ejecuta
     * «php artisan config:cache», y el ajuste dejaría de aplicarse sin que
     * nada avisara.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    public function __construct()
    {
        $configurados = config('app.trusted_proxies');

        if (blank($configurados)) {
            return;
        }

        $this->proxies = $configurados === '*'
            ? '*'
            : array_map('trim', explode(',', (string) $configurados));
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
