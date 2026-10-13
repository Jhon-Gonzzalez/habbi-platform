<?php

namespace Database\Seeders;

use App\Models\Alojamiento;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SolicitudArrendador;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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

            $this->crearSolicitudes($estudiantes);
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

    /**
     * Dos solicitudes de arrendador, para que la pantalla de revisión no
     * esté vacía al probar la aplicación.
     *
     * Una pendiente CON documentos, para poder aprobarla y ver cómo se
     * borran solos. Otra ya resuelta y con los documentos eliminados, que
     * es como se ve una solicitud vieja.
     */
    private function crearSolicitudes($estudiantes): void
    {
        $pendiente = $estudiantes[0];
        $resuelta  = $estudiantes[1];

        SolicitudArrendador::updateOrCreate(
            ['user_id' => $pendiente->id],
            [
                'estado'         => SolicitudArrendador::PENDIENTE,
                'documento_path' => $this->documentoDeMentira($pendiente->id, 'documento', 'CÉDULA'),
                'prueba_path'    => $this->documentoDeMentira($pendiente->id, 'prueba', 'RECIBO'),
                'telefono'       => '+57 300 555 0199',
                'mensaje'        => 'Mi mamá tiene dos cuartos libres desde que mis hermanos se fueron '
                    . 'a estudiar a Medellín. Queremos arrendarlos a estudiantes de Unicórdoba.',
            ],
        );

        SolicitudArrendador::updateOrCreate(
            ['user_id' => $resuelta->id],
            [
                'estado'                 => SolicitudArrendador::RECHAZADA,
                'documento_path'         => 'verificaciones/' . $resuelta->id . '/documento/borrado.jpg',
                'prueba_path'            => 'verificaciones/' . $resuelta->id . '/prueba/borrado.jpg',
                'documentos_borrados_en' => now()->subDays(12),
                'telefono'               => '+57 300 555 0198',
                'mensaje'                => 'Quiero publicar el apartamento de mi tío.',
                'motivo_rechazo'         => 'El recibo que subiste está a nombre de otra persona. '
                    . 'Necesitamos uno a tu nombre, o una autorización firmada del propietario.',
                'revisada_en'            => now()->subDays(12),
            ],
        );
    }

    /**
     * Genera un archivo que hace de documento subido.
     *
     * Es un rectángulo con una palabra, NO una cédula de verdad ni una
     * imagen sacada de internet: en los datos de ejemplo no tiene por qué
     * aparecer el documento de nadie.
     */
    private function documentoDeMentira(int $userId, string $tipo, string $texto): string
    {
        $ruta = 'verificaciones/' . $userId . '/' . $tipo . '/' . $tipo . '-ejemplo.png';

        $img = imagecreatetruecolor(900, 560);
        imagefilledrectangle($img, 0, 0, 900, 560, imagecolorallocate($img, 229, 235, 243));
        imagefilledrectangle($img, 40, 40, 860, 520, imagecolorallocate($img, 255, 255, 255));

        $tinta = imagecolorallocate($img, 102, 112, 133);
        imagestring($img, 5, 70, 80, $texto . ' DE EJEMPLO', $tinta);
        imagestring($img, 3, 70, 120, 'Documento ficticio generado por el seeder.', $tinta);
        imagestring($img, 3, 70, 145, 'No contiene datos de ninguna persona real.', $tinta);

        for ($y = 220; $y < 460; $y += 42) {
            imagefilledrectangle($img, 70, $y, 70 + rand(380, 720), $y + 14,
                imagecolorallocate($img, 223, 228, 237));
        }

        ob_start();
        imagepng($img);
        Storage::disk('private')->put($ruta, ob_get_clean());
        imagedestroy($img);

        return $ruta;
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

                // La mayoría se puede ocupar ya (null); unos pocos traen
                // fecha, para que la etiqueta se vea en el listado.
                'disponible_desde'       => isset($fila['disponible'])
                    ? now()->modify($fila['disponible'])->startOfDay()
                    : null,
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
