<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Escribe en el registro de auditoría.
 *
 * Se usa como `Auditor::registrar(...)` desde los controladores del panel.
 * Nunca lanza excepciones: si el registro falla, se anota en el log del
 * servidor pero NO se tumba la acción que el administrador estaba haciendo.
 * Perder una línea de auditoría es malo; dejar a medias el borrado de una
 * reseña porque no se pudo escribir esa línea es peor.
 */
class Auditor
{
    /**
     * @param  string       $accion       Una de las constantes de Auditoria.
     * @param  string       $descripcion  Frase legible para un humano.
     * @param  Model|null   $objeto       Sobre qué se actuó.
     * @param  array<mixed> $datos        Contexto extra (motivos, valores previos).
     */
    public static function registrar(
        string $accion,
        string $descripcion,
        ?Model $objeto = null,
        array $datos = [],
        ?User $actor = null,
    ): ?Auditoria {
        $actor ??= auth()->user();

        try {
            return Auditoria::create([
                'user_id'       => $actor?->id,
                'actor_nombre'  => $actor?->name ?? 'sistema',
                'actor_correo'  => $actor?->email ?? '—',
                'accion'        => $accion,
                'descripcion'   => $descripcion,
                'objeto_tipo'   => $objeto ? class_basename($objeto) : null,
                'objeto_id'     => $objeto?->getKey(),
                'objeto_nombre' => $objeto ? self::nombrar($objeto) : null,
                'datos'         => $datos ?: null,
                'ip'            => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo escribir en la auditoría.', [
                'accion' => $accion,
                'error'  => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** Un nombre reconocible del objeto, para no depender de su id. */
    private static function nombrar(Model $objeto): ?string
    {
        foreach (['title', 'name', 'email'] as $campo) {
            if (! empty($objeto->{$campo})) {
                return mb_substr((string) $objeto->{$campo}, 0, 255);
            }
        }

        return null;
    }
}
