<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Alta y edición de usuarios desde el panel de administración. */
class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $creando = $this->isMethod('POST');

        $reglas = [
            'name'     => ['required', 'string', 'max:120'],
            'email'    => [
                'required', 'string', 'email', 'max:180',
                Rule::unique('users', 'email')->ignore($usuario?->id),
            ],
            'phone'    => ['nullable', 'string', 'max:40'],
            'role'     => ['required', Rule::in([User::ROLE_USER, User::ROLE_ADMIN])],
        ];

        // Al CREAR hace falta una contraseña inicial. Al EDITAR no se admite:
        // un administrador no debe poder fijar la contraseña de otra persona,
        // porque eso equivale a poder entrar en su cuenta. Para ese caso está
        // el enlace de restablecimiento, que solo puede usar el propio dueño.
        if ($creando) {
            $reglas['password'] = ['required', 'confirmed', Password::min(8)->letters()->numbers()];
        }

        return $reglas;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name'     => 'nombre',
            'email'    => 'correo electrónico',
            'phone'    => 'teléfono',
            'role'     => 'rol',
            'password' => 'contraseña',
        ];
    }
}
