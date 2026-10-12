<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudArrendadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crear', \App\Models\SolicitudArrendador::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'telefono'  => ['required', 'string', 'regex:/^[0-9+\s()-]{7,20}$/'],
            'mensaje'   => ['required', 'string', 'min:40', 'max:1000'],
            'documento' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'prueba'    => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'acepta'    => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'telefono'  => 'teléfono',
            'mensaje'   => 'descripción',
            'documento' => 'documento de identidad',
            'prueba'    => 'prueba del inmueble',
            'acepta'    => 'autorización de tratamiento de datos',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'mensaje.min'      => 'Cuéntanos con algo más de detalle: al menos 40 caracteres.',
            'documento.mimes'  => 'El documento debe ser una foto (JPG, PNG, WEBP) o un PDF.',
            'prueba.mimes'     => 'La prueba del inmueble debe ser una foto (JPG, PNG, WEBP) o un PDF.',
            'acepta.accepted'  => 'Necesitamos tu autorización para tratar estos documentos.',
        ];
    }
}
