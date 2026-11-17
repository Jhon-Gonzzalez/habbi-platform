<?php

namespace App\Services;

use App\Models\Alojamiento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Centraliza la subida, el borrado y el orden de las fotos de un alojamiento.
 * Así los controladores no tocan el disco directamente.
 */
class AlojamientoPhotoService
{
    private const DIRECTORIO = 'alojamientos';
    private const DISCO      = 'public';

    public function __construct(private readonly ImagenOptimizada $optimizador = new ImagenOptimizada) {}

    /**
     * Guarda los archivos recibidos y devuelve sus rutas relativas.
     *
     * @param  array<int, UploadedFile|null>  $archivos
     * @return array<int, string>
     */
    public function guardar(array $archivos): array
    {
        $rutas = [];

        foreach ($archivos as $archivo) {
            if (! $archivo instanceof UploadedFile || ! $archivo->isValid()) {
                continue;
            }

            $rutas[] = $this->guardarUna($archivo);
        }

        return $rutas;
    }

    /**
     * Guarda una foto, reducida y recomprimida.
     *
     * Un celular saca fotos de 4000px y 5 MB. El coste de guardarlas tal
     * cual no lo paga quien sube, que lo hace una vez: lo paga cada
     * estudiante que abre la publicación desde Montería con datos móviles.
     *
     * Si GD no puede con el archivo se guarda el original. Vale más una
     * foto pesada que una publicación sin fotos: la validación ya comprobó
     * que es una imagen de un tipo admitido.
     */
    private function guardarUna(UploadedFile $archivo): string
    {
        $optimizada = $this->optimizador->comprimir((string) file_get_contents($archivo->getRealPath()));

        if ($optimizada === null) {
            return $archivo->store(self::DIRECTORIO, self::DISCO);
        }

        $ruta = self::DIRECTORIO . '/' . $archivo->hashName('') . '.jpg';

        Storage::disk(self::DISCO)->put($ruta, $optimizada);

        return $ruta;
    }

    /**
     * Sincroniza la galería: quita las fotos marcadas, añade las nuevas
     * y recalcula la portada. Los archivos eliminados se borran del disco.
     *
     * @param  array<int, UploadedFile|null>  $nuevas
     * @param  array<int, string>             $aEliminar  rutas a quitar
     */
    public function sincronizar(
        Alojamiento $alojamiento,
        array $nuevas = [],
        array $aEliminar = [],
        ?string $portada = null,
    ): void {
        $fotos = collect($alojamiento->photos ?? []);

        // 1. Quitar las marcadas para eliminar (solo las que pertenecen a este alojamiento)
        $eliminables = $fotos->intersect($aEliminar);
        $fotos       = $fotos->reject(fn ($p) => $eliminables->contains($p));
        $this->borrarArchivos($eliminables->all());

        // 2. Añadir las nuevas
        $fotos = $fotos->merge($this->guardar($nuevas))->unique()->values();

        // 3. Recalcular la portada
        $portadaFinal = $alojamiento->cover_path;

        if ($portada && $fotos->contains($portada)) {
            $portadaFinal = $portada;
        }

        if (!$portadaFinal || !$fotos->contains($portadaFinal)) {
            $portadaFinal = $fotos->first();
        }

        $alojamiento->forceFill([
            'photos'     => $fotos->all(),
            'cover_path' => $portadaFinal,
        ])->save();
    }

    /** Borra del disco todas las fotos de un alojamiento. */
    public function eliminarTodas(Alojamiento $alojamiento): void
    {
        $rutas = collect($alojamiento->photos ?? [])
            ->push($alojamiento->cover_path)
            ->filter()
            ->unique()
            ->all();

        $this->borrarArchivos($rutas);
    }

    /** @param  array<int, string>  $rutas */
    private function borrarArchivos(array $rutas): void
    {
        foreach ($rutas as $ruta) {
            if ($ruta && Storage::disk(self::DISCO)->exists($ruta)) {
                Storage::disk(self::DISCO)->delete($ruta);
            }
        }
    }
}
