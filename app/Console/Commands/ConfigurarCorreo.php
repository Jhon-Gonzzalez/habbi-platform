<?php

namespace App\Console\Commands;

use App\Support\ArchivoEnv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Deja el envío de correo funcionando y lo COMPRUEBA mandando uno de prueba.
 *
 *   php artisan habbi:correo           # configurar Gmail paso a paso
 *   php artisan habbi:correo --probar  # solo probar lo que ya hay puesto
 *   php artisan habbi:correo --log     # volver al modo local (no envía)
 *
 * Existe porque configurar SMTP a mano tiene demasiados sitios donde
 * equivocarse: siete variables, un config:clear que se olvida, y ninguna
 * señal de si funcionó hasta que un usuario se queja.
 */
class ConfigurarCorreo extends Command
{
    protected $signature = 'habbi:correo
                            {--probar : Solo enviar un correo de prueba con la configuración actual}
                            {--log : Volver al modo local, donde los correos van al log}
                            {--a= : A qué dirección mandar la prueba}
                            {--host= : Servidor SMTP, si no usas Gmail}
                            {--port= : Puerto SMTP, si no usas Gmail}';

    protected $description = 'Configura el envío de correo y comprueba que llega';

    public function handle(): int
    {
        $env = ArchivoEnv::enLaRaiz();

        if (! $env->existe()) {
            $this->error('No hay archivo .env. Ejecuta primero: bash deploy/probar-local.sh');

            return self::FAILURE;
        }

        if ($this->option('log')) {
            return $this->volverAlLog($env);
        }

        if (! $this->option('probar')) {
            if (! $this->configurarGmail($env)) {
                return self::FAILURE;
            }
        }

        return $this->enviarPrueba();
    }

    private function volverAlLog(ArchivoEnv $env): int
    {
        $env->escribirVarias([
            'MAIL_MAILER'       => 'log',
            'MAIL_FROM_ADDRESS' => 'no-responder@habbi.local',
            'MAIL_FROM_NAME'    => 'HABBI',
        ]);

        $this->recargarConfiguracion();

        $this->info('Listo: los correos vuelven a escribirse en storage/logs/laravel.log.');
        $this->line('Para verificar una cuenta: php artisan habbi:enlace tucorreo@ejemplo.com --verificar');

        return self::SUCCESS;
    }

    private function configurarGmail(ArchivoEnv $env): bool
    {
        $this->line('');
        $this->line('  Para que los correos lleguen de verdad hace falta una CONTRASEÑA');
        $this->line('  DE APLICACIÓN de Google. No es la contraseña de tu Gmail.');
        $this->line('');
        $this->line('    1. Activa la verificación en dos pasos:');
        $this->line('       https://myaccount.google.com/security');
        $this->line('    2. Crea la contraseña de aplicación (16 letras):');
        $this->line('       https://myaccount.google.com/apppasswords');
        $this->line('');
        $this->line('  Si el paso 2 te dice que no está disponible, es que falta el paso 1.');
        $this->line('');

        $correo = $this->ask('Tu dirección de Gmail');

        if (! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->error("«{$correo}» no parece una dirección de correo.");

            return false;
        }

        // secret() no muestra lo que se escribe, para que la contraseña no
        // quede a la vista de quien pase ni en una captura de pantalla.
        $clave = $this->secret('Contraseña de aplicación (16 letras, no se verá al escribir)');

        // Google la muestra en grupos de cuatro: «abcd efgh ijkl mnop». Casi
        // todo el mundo la pega con los espacios y SMTP la rechaza.
        $clave = preg_replace('/\s+/', '', (string) $clave);

        if (strlen($clave) !== 16) {
            $this->warn(sprintf(
                'Aviso: las contraseñas de aplicación de Google tienen 16 letras y esta tiene %d. '
                . 'Si falla la prueba, revisa que la hayas copiado entera.',
                strlen($clave)
            ));
        }

        $env->escribirVarias([
            'MAIL_MAILER'       => 'smtp',
            'MAIL_HOST'         => $this->option('host') ?: 'smtp.gmail.com',
            'MAIL_PORT'         => (string) ($this->option('port') ?: 587),
            'MAIL_USERNAME'     => $correo,
            'MAIL_PASSWORD'     => $clave,
            'MAIL_ENCRYPTION'   => 'tls',
            'MAIL_FROM_ADDRESS' => $correo,
            'MAIL_FROM_NAME'    => 'HABBI',
        ]);

        $this->recargarConfiguracion();
        $this->info('Configuración guardada en .env.');

        return true;
    }

    private function enviarPrueba(): int
    {
        $destino = $this->option('a') ?: config('mail.from.address');

        $this->line('');
        $this->line("  Enviando un correo de prueba a {$destino} (vía " . config('mail.default') . ')…');

        try {
            Mail::raw(
                "Si estás leyendo esto, el correo de HABBI funciona.\n\n"
                . "Enviado el " . now()->format('d/m/Y \a \l\a\s H:i') . ".",
                fn ($m) => $m->to($destino)->subject('HABBI · prueba de correo'),
            );
        } catch (\Throwable $e) {
            $this->line('');
            $this->error('No se pudo enviar.');
            $this->line('');
            $this->line('  ' . $e->getMessage());
            $this->line('');
            $this->explicar($e->getMessage());

            return self::FAILURE;
        }

        $this->line('');

        if (config('mail.default') === 'log') {
            $this->info('Escrito en storage/logs/laravel.log (MAIL_MAILER=log no envía nada).');
            $this->line('Para que llegue de verdad: php artisan habbi:correo');

            return self::SUCCESS;
        }

        $this->info("Enviado. Revisa la bandeja de {$destino} (mira también en spam).");

        return self::SUCCESS;
    }

    /** Traduce los errores de SMTP más comunes a algo accionable. */
    private function explicar(string $error): void
    {
        $pistas = [
            'Username and Password not accepted' =>
                'Google rechazó la contraseña. Casi siempre es porque pusiste la contraseña '
                . 'normal de Gmail en vez de una contraseña de APLICACIÓN. Créala en '
                . 'https://myaccount.google.com/apppasswords',
            'Authentication failed' =>
                'Usuario o contraseña incorrectos. Revisa que el correo esté bien escrito y '
                . 'que la contraseña de aplicación esté completa (16 letras, sin espacios).',
            'Connection could not be established' =>
                'No se pudo conectar con el servidor. Revisa tu conexión a internet, o si '
                . 'una red del trabajo o la universidad bloquea el puerto 587.',
            'Could not authenticate' =>
                'Google no aceptó las credenciales. Si acabas de activar la verificación en '
                . 'dos pasos, genera la contraseña de aplicación DESPUÉS de activarla.',
            'must have a "From"' =>
                'Falta el remitente. Ejecuta: php artisan habbi:correo',
        ];

        foreach ($pistas as $fragmento => $explicacion) {
            if (str_contains($error, $fragmento)) {
                $this->warn('  ¿Qué significa?');
                $this->line('  ' . wordwrap($explicacion, 72, "\n  "));
                $this->line('');

                return;
            }
        }

        $this->line('  Mientras lo resuelves puedes seguir trabajando en modo local:');
        $this->line('      php artisan habbi:correo --log');
        $this->line('');
    }

    /** Sin esto, la configuración recién escrita no se aplica a esta misma ejecución. */
    private function recargarConfiguracion(): void
    {
        $this->callSilently('config:clear');

        // Se relee el .env recién escrito: la configuración ya cargada en
        // memoria todavía tiene los valores de antes.
        $env = ArchivoEnv::enLaRaiz();

        config([
            'mail.default'              => $env->leer('MAIL_MAILER') ?: 'log',
            'mail.from.address'         => $env->leer('MAIL_FROM_ADDRESS') ?: 'no-responder@habbi.local',
            'mail.from.name'            => $env->leer('MAIL_FROM_NAME') ?: 'HABBI',
            'mail.mailers.smtp.host'     => $env->leer('MAIL_HOST') ?: 'smtp.gmail.com',
            'mail.mailers.smtp.port'     => (int) ($env->leer('MAIL_PORT') ?: 587),
            'mail.mailers.smtp.username' => $env->leer('MAIL_USERNAME'),
            'mail.mailers.smtp.password' => $env->leer('MAIL_PASSWORD'),
            'mail.mailers.smtp.encryption' => $env->leer('MAIL_ENCRYPTION') ?: 'tls',
        ]);

        // El gestor de correo cachea los «mailers» ya construidos con la
        // configuración vieja; hay que tirarlo para que lea la nueva.
        app()->forgetInstance('mail.manager');
        Mail::clearResolvedInstances();
    }
}
