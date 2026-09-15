<?php

namespace App\Providers;

use App\Models\Alojamiento;
use App\Models\Rating;
use App\Policies\AlojamientoPolicy;
use App\Policies\RatingPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Mapeo de modelos a policies.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Alojamiento::class => AlojamientoPolicy::class,
        Rating::class      => RatingPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
