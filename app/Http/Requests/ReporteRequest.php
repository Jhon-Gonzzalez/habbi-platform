<?php

namespace App\Http\Requests;

use App\Models\Reporte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'motivo'  => ['required', Rule::in(array_keys(Reporte::MOTIVOS))],
            // Si el motivo es "otro", el detalle deja de ser opcional: sin él
            // el administrador no tendría nada que revisar.
            'detalle' => [
                Rule::requiredIf(fn () => $this->input('motivo') === 'otro'),
                'nullable', 'string', 'min:15', 'max:1000',
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'motivo'  => 'motivo',
            'detalle' => 'explicación',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'detalle.required' => 'Si eliges «Otro motivo», explícanos qué pasó.',
            'detalle.min'      => 'Cuéntanos un poco más (al menos 15 caracteres) para poder revisarlo.',
        ];
    }
}
