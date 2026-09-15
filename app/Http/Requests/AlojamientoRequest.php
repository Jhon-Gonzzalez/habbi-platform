<?php

namespace App\Http\Requests;

use App\Models\Alojamiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas compartidas por la creación y la edición de un alojamiento.
 * La autorización la resuelven las policies en las rutas.
 */
class AlojamientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $reglas = [
            'title'        => ['required', 'string', 'min:10', 'max:255'],
            'type'         => ['required', Rule::in(Alojamiento::TIPOS)],
            'price'        => ['required', 'integer', 'min:1', 'max:100000000'],
            'price_period' => ['required', Rule::in(Alojamiento::PERIODOS)],
            'guests'       => ['required', 'integer', 'min:1', 'max:8'],
            'city'         => ['required', 'string', 'max:120'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'address'      => ['nullable', 'string', 'max:255'],
            'description'  => ['required', 'string', 'min:30', 'max:5000'],
            'amenities'    => ['nullable', 'array'],
            'amenities.*'  => [Rule::in(Alojamiento::COMODIDADES)],
            'phone'        => ['required', 'string', 'regex:/^[0-9+\s()-]{7,20}$/'],
            'is_active'    => ['nullable', 'boolean'],
        ];

        if ($this->isMethod('POST')) {
            $reglas['photos']   = ['required', 'array', 'min:1', 'max:8'];
            $reglas['photos.*'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        } else {
            $reglas['new_photos']     = ['nullable', 'array', 'max:8'];
            $reglas['new_photos.*']   = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
            $reglas['delete_photos']  = ['nullable', 'array'];
            $reglas['delete_photos.*'] = ['string'];
            $reglas['cover_path']     = ['nullable', 'string'];
        }

        return $reglas;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'title'        => 'título',
            'type'         => 'tipo de alojamiento',
            'price'        => 'precio',
            'price_period' => 'periodo de precio',
            'guests'       => 'número de huéspedes',
            'city'         => 'ciudad',
            'neighborhood' => 'barrio',
            'address'      => 'dirección',
            'description'  => 'descripción',
            'amenities'    => 'comodidades',
            'phone'        => 'teléfono de contacto',
            'photos'       => 'fotos',
            'new_photos'   => 'fotos nuevas',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'photos.required'   => 'Sube al menos una foto del alojamiento.',
            'phone.regex'       => 'El teléfono solo puede contener números, espacios y los signos + ( ) -',
            'description.min'   => 'Describe el alojamiento con al menos 30 caracteres para que resulte útil.',
            'title.min'         => 'El título debe tener al menos 10 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
