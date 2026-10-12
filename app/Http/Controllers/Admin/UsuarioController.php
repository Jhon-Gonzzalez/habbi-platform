<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UsuarioRequest;
use App\Models\Auditoria;
use App\Models\User;
use App\Services\Auditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class UsuarioController extends Controller
{
    /** Listado con buscador. */
    public function index(Request $request): View
    {
        $usuarios = User::query()
            ->withCount(['alojamientos', 'ratings'])
            ->when($request->input('q'), function ($sql, string $q) {
                $term = '%' . addcslashes($q, '%_\\') . '%';
                $sql->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('admin.usuarios.create');
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        $usuario = User::create($request->validated());

        Auditor::registrar(
            Auditoria::USUARIO_CREADO,
            'Creó la cuenta de ' . $usuario->name . ' con rol ' . (User::ROLES[$usuario->role] ?? $usuario->role),
            $usuario,
            ['rol' => $usuario->role],
        );

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $usuario): View
    {
        $usuario->loadCount(['alojamientos', 'ratings']);

        return view('admin.usuarios.show', [
            'usuario'      => $usuario,
            'alojamientos' => $usuario->alojamientos()->conResenas()->latest()->get(),
        ]);
    }

    public function edit(User $usuario): View
    {
        return view('admin.usuarios.edit', compact('usuario'));
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();

        // Solo cambia la contraseña si el formulario la trae.
        if (blank($datos['password'] ?? null)) {
            unset($datos['password']);
        }

        // Evita que el último administrador se quite a sí mismo el rol.
        if ($usuario->isAdmin() && $datos['role'] !== User::ROLE_ADMIN && $this->esUltimoAdmin($usuario)) {
            return back()->withInput()->with('error', 'No puedes quitar el rol al único administrador que queda.');
        }

        // Se compara antes de guardar para registrar QUÉ cambió, sin
        // guardar nunca el valor de la contraseña ni su hash.
        $rolAnterior    = $usuario->role;
        $correoAnterior = $usuario->email;

        $usuario->update($datos);

        $cambios = [];
        if ($rolAnterior !== $usuario->role) {
            $cambios['rol'] = ['antes' => $rolAnterior, 'ahora' => $usuario->role];
        }
        if ($correoAnterior !== $usuario->email) {
            $cambios['correo'] = ['antes' => $correoAnterior, 'ahora' => $usuario->email];
        }
        if (array_key_exists('password', $datos)) {
            $cambios['contrasena'] = 'cambiada';
        }

        if ($cambios) {
            Auditor::registrar(
                Auditoria::USUARIO_EDITADO,
                'Editó la cuenta de ' . $usuario->name . ' (' . implode(', ', array_keys($cambios)) . ')',
                $usuario,
                $cambios,
            );
        }

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Genera un enlace de restablecimiento para que el usuario recupere el
     * acceso él mismo. El administrador nunca llega a conocer la contraseña:
     * el enlace caduca y solo sirve para esa cuenta.
     */
    public function enlaceReset(User $usuario): RedirectResponse
    {
        $token = Password::broker()->createToken($usuario);
        $enlace = route('password.reset', ['token' => $token, 'email' => $usuario->email]);

        // Se intenta enviar por correo; si no hay correo configurado, el
        // administrador puede pasarle el enlace por otro medio.
        try {
            Password::broker()->sendResetLink(['email' => $usuario->email]);
            $aviso = 'Enlace enviado a ' . $usuario->email . '.';
        } catch (\Throwable $e) {
            $aviso = 'No se pudo enviar el correo, pero el enlace ya está generado.';
        }

        Auditor::registrar(
            Auditoria::ENLACE_RESET_GENERADO,
            'Generó un enlace de restablecimiento para ' . $usuario->name,
            $usuario,
            // El token NO se guarda: el registro dice que se generó, no deja
            // una llave usable en una tabla que lee cualquier administrador.
            ['enviado_por_correo' => str_starts_with($aviso, 'Enlace enviado')],
        );

        return back()
            ->with('success', $aviso)
            ->with('enlace_reset', $enlace);
    }

    /**
     * Marca el correo como verificado a mano.
     *
     * Existe porque el envío de correo falla más de lo que uno quisiera
     * (SMTP mal configurado, el mensaje en spam, un dominio que rebota), y
     * sin esta salida un usuario legítimo se queda sin poder reseñar ni pedir
     * ser arrendador. No da ningún permiso por sí sola: solo dice «este
     * correo lo comprobamos por otro medio».
     */
    public function verificarCorreo(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->hasVerifiedEmail()) {
            return back()->with('success', 'Ese correo ya estaba verificado.');
        }

        $usuario->markEmailAsVerified();

        Auditor::registrar(
            Auditoria::CORREO_VERIFICADO,
            'Marcó como verificado el correo de ' . $usuario->name,
            $usuario,
        );

        return back()->with('success', 'Correo de ' . $usuario->name . ' marcado como verificado.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta desde el panel.');
        }

        if ($usuario->isAdmin() && $this->esUltimoAdmin($usuario)) {
            return back()->with('error', 'No puedes eliminar al único administrador que queda.');
        }

        Auditor::registrar(
            Auditoria::USUARIO_BORRADO,
            'Eliminó la cuenta de ' . $usuario->name . ' (' . $usuario->email . ')',
            $usuario,
            [
                'rol'           => $usuario->role,
                'alojamientos'  => $usuario->alojamientos()->count(),
                'resenas'       => $usuario->ratings()->count(),
                'registrado_el' => $usuario->created_at?->toDateString(),
            ],
        );

        $usuario->delete();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario eliminado. La acción quedó registrada en la auditoría.');
    }

    private function esUltimoAdmin(User $usuario): bool
    {
        return User::where('role', User::ROLE_ADMIN)->where('id', '!=', $usuario->id)->doesntExist();
    }
}
