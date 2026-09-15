<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'rating.required' => 'Selecciona cuántas estrellas le das al alojamiento.',
            'rating.min'      => 'Selecciona cuántas estrellas le das al alojamiento.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'rating'  => 'calificación',
            'comment' => 'comentario',
        ];
    }
}
