<?php

namespace App\Services;

use App\Models\SolicitudArrendador;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Flujo completo de verificación de arrendadores.
 *
 * Los documentos (cédula y prueba del inmueble) se guardan en el disco
 * 'private', que vive fuera de public/ y no tiene URL. Solo se sirven a
 * través de un controlador que comprueba permisos antes de entregarlos.
 */
class VerificacionArrendadorService
{
    private const DISCO = 'private';
    private const CARPETA = 'verificaciones';

    /** Registra una solicitud nueva con sus documentos. */
    public function solicitar(
        User $usuario,
        UploadedFile $documento,
        UploadedFile $prueba,
        string $telefono,
        string $mensaje,
    ): SolicitudArrendador {
        return $usuario->solicitudes()->create([
            'estado'         => SolicitudArrendador::PENDIENTE,
            'documento_path' => $this->guardar($documento, $usuario, 'documento'),
            'prueba_path'    => $this->guardar($prueba, $usuario, 'prueba'),
            'telefono'       => $telefono,
            'mensaje'        => $mensaje,
        ]);
    }

    /**
     * Aprueba la solicitud: el usuario pasa a arrendador y queda verificado.
     * Va en una transacción porque son dos tablas y no pueden quedar
     * desacompasadas (un arrendador sin sello, o un sello sin rol).
     */
    public function aprobar(SolicitudArrendador $solicitud, User $revisor): void
    {
        DB::transaction(function () use ($solicitud, $revisor) {
            $solicitud->update([
                'estado'       => SolicitudArrendador::APROBADA,
                'revisada_por' => $revisor->id,
                'revisada_en'  => now(),
            ]);

            $solicitud->user->forceFill([
                'role'          => User::ROLE_ARRENDADOR,
                'verificado_en' => now(),
                'phone'         => $solicitud->user->phone ?: $solicitud->telefono,
            ])->save();
        });
    }

    /** Rechaza la solicitud explicando por qué; el usuario puede volver a enviarla. */
    public function rechazar(SolicitudArrendador $solicitud, User $revisor, string $motivo): void
    {
        $solicitud->update([
            'estado'         => SolicitudArrendador::RECHAZADA,
            'motivo_rechazo' => $motivo,
            'revisada_por'   => $revisor->id,
            'revisada_en'    => now(),
        ]);
    }

    /**
     * Retira la condición de arrendador y borra el sello. Las publicaciones
     * no se tocan: eso lo decide el administrador aparte.
     */
    public function revocar(User $usuario): void
    {
        $usuario->forceFill([
            'role'          => User::ROLE_ESTUDIANTE,
            'verificado_en' => null,
        ])->save();
    }

    /** Borra del disco los documentos de una solicitud ya resuelta. */
    public function eliminarDocumentos(SolicitudArrendador $solicitud): void
    {
        foreach ([$solicitud->documento_path, $solicitud->prueba_path] as $ruta) {
            if ($ruta && Storage::disk(self::DISCO)->exists($ruta)) {
                Storage::disk(self::DISCO)->delete($ruta);
            }
        }
    }

    /** Devuelve el contenido de un documento para entregarlo tras comprobar permisos. */
    public function rutaCompleta(string $ruta): ?string
    {
        return Storage::disk(self::DISCO)->exists($ruta)
            ? Storage::disk(self::DISCO)->path($ruta)
            : null;
    }

    private function guardar(UploadedFile $archivo, User $usuario, string $tipo): string
    {
        return $archivo->store(self::CARPETA . '/' . $usuario->id . '/' . $tipo, self::DISCO);
    }
}
