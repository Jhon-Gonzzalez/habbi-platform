<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UsuarioRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        User::create($request->validated());

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

        $usuario->update($datos);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta desde el panel.');
        }

        if ($usuario->isAdmin() && $this->esUltimoAdmin($usuario)) {
            return back()->with('error', 'No puedes eliminar al único administrador que queda.');
        }

        $usuario->delete();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    private function esUltimoAdmin(User $usuario): bool
    {
        return User::where('role', User::ROLE_ADMIN)->where('id', '!=', $usuario->id)->doesntExist();
    }
}
