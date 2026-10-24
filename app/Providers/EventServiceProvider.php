<?php

namespace App\Providers;

use App\Listeners\EnviarVerificacionDeCorreo;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            EnviarVerificacionDeCorreo::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * No se registra el listener de verificación que trae Laravel.
     *
     * Desde Laravel 11, el framework engancha SendEmailVerificationNotification
     * al evento Registered por su cuenta, además del que declaremos aquí. Con
     * los dos activos pasaban dos cosas malas: se enviaban DOS correos de
     * verificación, y el de serie —que no atrapa excepciones— devolvía un 500
     * justo después de crear la cuenta si el SMTP estaba caído, dejando a la
     * persona ante una pantalla de error sin saber si quedó registrada.
     *
     * EnviarVerificacionDeCorreo hace lo mismo pero sin tumbar el registro,
     * así que aquí se desactiva el de serie a propósito. Sobrescribir este
     * método es la única forma de evitarlo: el framework solo se abstiene si
     * la clase ya figura en $listen, lo que lo volvería a activar.
     */
    protected function configureEmailVerification(): void
    {
        // Vacío queriendo. Lo nuestro está en $listen.
    }
}
