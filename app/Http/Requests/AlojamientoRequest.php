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

            /*
             * Coordenadas: las pone el arrendador moviendo el pin en el
             * mapa, no un servicio de geocodificación. Se validan como
             * pareja porque una sola no sirve de nada: con media
             * coordenada guardada, el filtro por distancia devolvería
             * resultados sin sentido en vez de no devolver ninguno.
             *
             * El rango es el del planeta y no el de Montería a propósito:
             * la plataforma nace aquí, pero nada en el código impide que
             * se use en otra ciudad, y un límite geográfico escondido en
             * una regla de validación es de los errores más difíciles de
             * encontrar el día que eso pase.
             */
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],

            // Solo 'date': no se exige que sea futura porque al editar un
            // anuncio viejo la fecha ya pasó, y rechazarla obligaría a
            // borrarla para poder guardar cualquier otro cambio.
            'disponible_desde' => ['nullable', 'date'],
            // 'sometimes' y no 'required': casi siempre es 1 y la base de
            // datos ya pone ese valor por defecto. Exigirlo solo añadiría un
            // campo más que rellenar para decir lo evidente.
            'cupos'            => ['sometimes', 'integer', 'min:1', 'max:50'],

            // Solo se acepta una universidad de la lista: así el enlace a
            // Google Maps siempre apunta a un destino que existe.
            'universidad_referencia' => [
                'nullable',
                Rule::in(array_column(Alojamiento::UNIVERSIDADES, 'nombre')),
            ],
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
            'lat'          => 'latitud',
            'lng'          => 'longitud',
            'disponible_desde' => 'fecha de disponibilidad',
            'cupos'            => 'plazas libres',
            'universidad_referencia' => 'universidad más cercana',
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
            'lat.required_with' => 'Vuelve a marcar el punto en el mapa: quedó a medias.',
            'lng.required_with' => 'Vuelve a marcar el punto en el mapa: quedó a medias.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);

        /*
         * Si el arrendador no tocó el mapa, los campos llegan vacíos. Sin
         * esto se guardarían como 0, que es una coordenada real (está en
         * el golfo de Guinea) y colocaría la publicación en el Atlántico.
         */
        foreach (['lat', 'lng'] as $campo) {
            if ($this->input($campo) === '') {
                $this->merge([$campo => null]);
            }
        }
    }
}
