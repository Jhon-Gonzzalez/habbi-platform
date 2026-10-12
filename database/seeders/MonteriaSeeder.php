<?php

namespace Database\Seeders;

use App\Models\Alojamiento;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Carga veinte alojamientos de ejemplo en Montería.
 *
 * Los barrios, calles, universidades y precios son reales; los negocios y
 * las personas son inventados. El porqué está explicado en el archivo de
 * datos: database/seeders/data/monteria.php
 *
 *   php artisan db:seed --class=MonteriaSeeder
 */
class MonteriaSeeder extends Seeder
{
    /** Comentarios por puntuación, para que las reseñas suenen a personas. */
    private const COMENTARIOS = [
        5 => [
            'Llevo dos semestres aquí y no me he querido mover. La señora es muy atenta y la casa es tranquila para estudiar.',
            'Excelente ubicación, llego a clase caminando. El wifi nunca se me ha caído en un parcial.',
            'Todo tal como aparece en la publicación. El arrendador contestó el mismo día y no pidió nada raro.',
            'Me recibieron sin codeudor porque vengo de otro municipio. Eso me salvó el semestre.',
            'El cuarto es fresco y limpio. Lo recomiendo a quien venga de fuera de Montería.',
        ],
        4 => [
            'Muy buena casa. Lo único es que el baño compartido en la mañana se llena, hay que madrugar.',
            'Buen precio para la zona. El ventilador no da mucho en abril, pero por el resto todo bien.',
            'La ubicación es lo mejor. La cocina sí queda pequeña cuando coincidimos tres cocinando.',
            'Cumplen con lo que ofrecen. Me habría gustado tener escritorio desde el principio.',
        ],
        3 => [
            'Está bien por el precio, pero el bus hasta la universidad se demora más de lo que pensaba.',
            'Limpio y seguro. Se escucha bastante la calle en el día, para estudiar me toca ir a la biblioteca.',
            'Cumple lo básico. No esperaría más de lo que dice el anuncio.',
        ],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('MonteriaSeeder omitido en producción.');

            return;
        }

        $datos = require database_path('seeders/data/monteria.php');

        DB::transaction(function () use ($datos) {
            $arrendadores = $this->crearArrendadores($datos['arrendadores']);
            $estudiantes  = $this->crearEstudiantes();

            foreach ($datos['alojamientos'] as $fila) {
                $alojamiento = $this->crearAlojamiento($fila, $arrendadores[$fila['arrendador']]);
                $this->crearResenas($alojamiento, $fila['rating'] ?? [], $estudiantes);
            }
        });

        $this->command?->info(sprintf(
            '%d alojamientos de Montería creados (%d arrendadores). Contraseña de todos: %s',
            count($datos['alojamientos']),
            count($datos['arrendadores']),
            'habbi1234'
        ));
    }

    /** @return array<string, User> indexado por nombre */
    private function crearArrendadores(array $filas): array
    {
        $arrendadores = [];

        foreach ($filas as $fila) {
            $arrendadores[$fila['name']] = User::updateOrCreate(
                ['email' => $fila['email']],
                [
                    'name'              => $fila['name'],
                    'password'          => Hash::make('habbi1234'),
                    'email_verified_at' => now(),
                    'role'          => User::ROLE_ARRENDADOR,
                    'phone'         => $fila['phone'],
                    'ciudad'        => $fila['ciudad'],
                    'bio'           => $fila['bio'],
                    // Solo los que el administrador habría aprobado llevan sello.
                    'verificado_en' => $fila['verificado'] ? now()->subMonths(rand(1, 14)) : null,
                ]
            );
        }

        return $arrendadores;
    }

    /** Estudiantes que firman las reseñas. */
    private function crearEstudiantes(): \Illuminate\Support\Collection
    {
        $nombres = [
            'Laura Hoyos', 'Kevin Doria', 'Valeria Pastrana', 'Juan David Cogollo',
            'Shirley Mejía', 'Andrés Burgos', 'Daniela Sierra', 'Óscar Villadiego',
            'Luisa Narváez', 'Brayan Oviedo', 'Katerine Pineda', 'Sebastián Flórez',
        ];

        $universidades = array_column(Alojamiento::UNIVERSIDADES, 'nombre');

        return collect($nombres)->map(function (string $nombre, int $i) use ($universidades) {
            $correo = str_replace(' ', '.', mb_strtolower($nombre)) . '@ejemplo.test';

            return User::updateOrCreate(
                ['email' => $correo],
                [
                    'name'              => $nombre,
                    'password'          => Hash::make('habbi1234'),
                    'email_verified_at' => now(),
                    'role'        => User::ROLE_ESTUDIANTE,
                    'ciudad'      => 'Montería',
                    'universidad' => $universidades[$i % count($universidades)],
                ]
            );
        });
    }

    private function crearAlojamiento(array $fila, User $arrendador): Alojamiento
    {
        return Alojamiento::updateOrCreate(
            [
                'user_id' => $arrendador->id,
                'title'   => $fila['title'],
            ],
            [
                'type'                   => $fila['type'],
                'price'                  => $fila['price'],
                'price_period'           => $fila['periodo'] ?? 'mes',
                'guests'                 => $fila['guests'],
                'city'                   => 'Montería',
                'neighborhood'           => $fila['neighborhood'],
                'address'                => $fila['address'],
                'universidad_referencia' => $fila['universidad'],
                'description'            => $fila['description'],
                'amenities'              => $fila['amenities'],
                'phone'                  => $arrendador->phone,
                // Sin fotos: no se incluyen imágenes de alojamientos reales ni
                // de bancos de imágenes. La vista ya muestra un marcador.
                'cover_path'             => null,
                'photos'                 => [],
                'is_active'              => true,
            ]
        );
    }

    /** @param  \Illuminate\Support\Collection<int, User>  $estudiantes */
    private function crearResenas(Alojamiento $alojamiento, array $puntuaciones, $estudiantes): void
    {
        $autores = $estudiantes->shuffle()->take(count($puntuaciones))->values();

        foreach ($puntuaciones as $i => $puntuacion) {
            $autor = $autores[$i] ?? null;

            if (! $autor) {
                continue;
            }

            Rating::updateOrCreate(
                [
                    'user_id'        => $autor->id,
                    'alojamiento_id' => $alojamiento->id,
                ],
                [
                    'rating'  => $puntuacion,
                    'comment' => self::COMENTARIOS[$puntuacion][array_rand(self::COMENTARIOS[$puntuacion])] ?? null,
                ]
            );
        }
    }
}
