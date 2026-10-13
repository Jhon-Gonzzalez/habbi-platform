<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /**
     * Reenvía el enlace de verificación.
     *
     * Se sobreescribe el del trait solo para no mentir: el de serie siempre
     * responde «te lo enviamos», incluso cuando el envío falló. Si alguien
     * pulsa el botón tres veces y nunca llega nada, merece saber que el
     * problema es nuestro y no su bandeja de spam.
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->redirectPath());
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::warning('Falló el reenvío del correo de verificación.', [
                'usuario' => $request->user()->getKey(),
                'error'   => $e->getMessage(),
            ]);

            // En local el que lee esto eres tú, desarrollando: decir «es un
            // problema nuestro» no ayuda a arreglarlo. Fuera de local, la
            // persona no tiene por qué ver detalles de configuración.
            if (app()->environment('local')) {
                return back()->with('error', sprintf(
                    'No se pudo enviar el correo (MAIL_MAILER=%s). %s · Saca el enlace con: php artisan habbi:enlace %s',
                    config('mail.default'),
                    $e->getMessage(),
                    $request->user()->email,
                ));
            }

            return back()->with('error', 'No pudimos enviar el correo ahora mismo. '
                . 'Es un problema nuestro, no tuyo. Inténtalo en un rato o escríbenos.');
        }

        return back()->with('resent', true);
    }
}
