<?php

namespace App\Http\Controllers;

use App\Models\Alojamiento;
use App\Services\ImagenMarcador;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sirve la imagen de marcador de un alojamiento sin foto.
 *
 * La primera petición la genera y la guarda; las siguientes la sirven
 * desde disco. Generar un PNG de 1200×900 cuesta unos milisegundos, pero
 * hacerlo en cada carga del listado serían veinte por página.
 */
class MarcadorController extends Controller
{
    private const CARPETA = 'marcadores';

    public function __invoke(Alojamiento $alojamiento, ImagenMarcador $generador): Response|BinaryFileResponse
    {
        $ruta = storage_path('app/public/' . self::CARPETA . '/' . $alojamiento->id . '.png');

        if (! is_file($ruta)) {
            @mkdir(dirname($ruta), 0775, true);
            file_put_contents($ruta, $generador->generar($alojamiento));
        }

        // Un año de caché: la imagen de un anuncio no cambia nunca. Si el
        // arrendador sube una foto de verdad, deja de usarse esta URL.
        return response()->file($ruta, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
