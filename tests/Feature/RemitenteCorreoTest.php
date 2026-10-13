<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Tests\TestCase;

/**
 * Un correo sin remitente no se puede construir, ni siquiera con el driver
 * "log" que no envía nada. Como muchos .env traen MAIL_FROM_ADDRESS vacío,
 * esto rompía la verificación de correo entera con un error críptico:
 * «An email must have a "From" or a "Sender" header».
 */
class RemitenteCorreoTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_remitente_vacio_cae_en_el_de_respaldo(): void
    {
        // env('X', 'defecto') NO cubre esto: el defecto solo aplica cuando la
        // variable no existe, no cuando existe vacía.
        $this->assertNotEmpty(
            $this->remitenteCon(''),
            'Con MAIL_FROM_ADDRESS vacío, el remitente quedó vacío.'
        );
    }

    public function test_se_respeta_el_remitente_cuando_si_esta_configurado(): void
    {
        $this->assertSame('hola@habbi.co', $this->remitenteCon('hola@habbi.co'));
    }

    public function test_se_puede_enviar_con_el_remitente_vacio(): void
    {
        config(['mail.from.address' => $this->remitenteCon('')]);

        $usuario = User::factory()->create(['email_verified_at' => null]);

        // Lo que importa: que no lance la excepción del «From».
        $usuario->sendEmailVerificationNotification();

        $this->assertTrue(true);
    }

    /**
     * Evalúa config/mail.php con MAIL_FROM_ADDRESS puesto a $valor.
     *
     * Se usa el repositorio de Env y no putenv(): env() lee de su propio
     * repositorio, así que con putenv el valor no cambia y el test pasaría
     * en vacío leyendo lo que haya en el .env de cada quien.
     */
    private function remitenteCon(string $valor): string
    {
        $repo     = Env::getRepository();
        $anterior = $repo->get('MAIL_FROM_ADDRESS');

        $repo->set('MAIL_FROM_ADDRESS', $valor);

        try {
            $config = require base_path('config/mail.php');

            return $config['from']['address'];
        } finally {
            $anterior === null
                ? $repo->clear('MAIL_FROM_ADDRESS')
                : $repo->set('MAIL_FROM_ADDRESS', $anterior);
        }
    }
}
