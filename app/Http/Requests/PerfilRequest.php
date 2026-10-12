<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición del perfil propio.
 *
 * Nota: aquí NO aparecen 'role' ni 'verificado_en'. Nadie se asciende a sí
 * mismo ni se pone su propio sello; eso lo decide un administrador tras
 * revisar documentos, y es justo lo que hace que el sello valga algo.
 */
class PerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'min:3', 'max:120'],
            'phone'       => ['nullable', 'string', 'regex:/^[0-9+\s()-]{7,20}$/'],
            'bio'         => ['nullable', 'string', 'max:600'],
            'ciudad'      => ['nullable', 'string', 'max:120'],
            'universidad' => ['nullable', 'string', 'max:160'],
            'foto'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'quitar_foto' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name'        => 'nombre',
            'phone'       => 'teléfono',
            'bio'         => 'descripción',
            'ciudad'      => 'ciudad',
            'universidad' => 'universidad',
            'foto'        => 'foto de perfil',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'foto.max'   => 'La foto no puede pesar más de 3 MB.',
            'foto.image' => 'La foto debe ser una imagen (JPG, PNG o WEBP).',
            'bio.max'    => 'La descripción no puede superar los 600 caracteres.',
        ];
    }
}
