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
            if ($archivo instanceof UploadedFile && $archivo->isValid()) {
                $rutas[] = $archivo->store(self::DIRECTORIO, self::DISCO);
            }
        }

        return $rutas;
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
