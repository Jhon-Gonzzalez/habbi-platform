<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de las acciones delicadas del panel de administración.
 *
 * POR QUÉ EXISTE
 * --------------
 * Un administrador puede borrar reseñas, tumbar publicaciones, aprobar
 * arrendadores y generar enlaces para entrar en cuentas ajenas. Si nada de
 * eso deja rastro, la única diferencia entre un administrador y alguien que
 * le robó la contraseña es la buena fe.
 *
 * El registro es de solo escritura: no hay forma de editarlo ni de borrarlo
 * desde la aplicación, y copia el nombre y el correo del actor para que
 * borrar la cuenta no borre su historial.
 */
class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditorias';

    /** Solo se escribe al crear; no hay updated_at porque nada se edita. */
    public const UPDATED_AT = null;

    /* ===================== Acciones ===================== */

    public const RESENA_BORRADA        = 'resena.borrada';
    public const ALOJAMIENTO_BORRADO   = 'alojamiento.borrado';
    public const ALOJAMIENTO_PAUSADO   = 'alojamiento.pausado';
    public const ALOJAMIENTO_ACTIVADO  = 'alojamiento.activado';
    public const USUARIO_CREADO        = 'usuario.creado';
    public const USUARIO_EDITADO       = 'usuario.editado';
    public const USUARIO_BORRADO       = 'usuario.borrado';
    public const ENLACE_RESET_GENERADO = 'usuario.enlace_reset';
    public const CORREO_VERIFICADO     = 'usuario.correo_verificado';
    public const ARRENDADOR_APROBADO   = 'arrendador.aprobado';
    public const ARRENDADOR_RECHAZADO  = 'arrendador.rechazado';
    public const ARRENDADOR_REVOCADO   = 'arrendador.revocado';
    public const REPORTE_RESUELTO      = 'reporte.resuelto';

    /** Nombre legible de cada acción, para la pantalla y sus filtros. */
    public const ETIQUETAS = [
        self::RESENA_BORRADA        => 'Borró una reseña',
        self::ALOJAMIENTO_BORRADO   => 'Eliminó un alojamiento',
        self::ALOJAMIENTO_PAUSADO   => 'Despublicó un alojamiento',
        self::ALOJAMIENTO_ACTIVADO  => 'Volvió a publicar un alojamiento',
        self::USUARIO_CREADO        => 'Creó un usuario',
        self::USUARIO_EDITADO       => 'Editó un usuario',
        self::USUARIO_BORRADO       => 'Eliminó un usuario',
        self::ENLACE_RESET_GENERADO => 'Generó un enlace de contraseña',
        self::CORREO_VERIFICADO     => 'Verificó un correo a mano',
        self::ARRENDADOR_APROBADO   => 'Aprobó un arrendador',
        self::ARRENDADOR_RECHAZADO  => 'Rechazó una solicitud',
        self::ARRENDADOR_REVOCADO   => 'Revocó un arrendador',
        self::REPORTE_RESUELTO      => 'Cerró un reporte',
    ];

    protected $fillable = [
        'user_id',
        'actor_nombre',
        'actor_correo',
        'accion',
        'descripcion',
        'objeto_tipo',
        'objeto_id',
        'objeto_nombre',
        'datos',
        'ip',
    ];

    protected $casts = [
        'datos'      => 'array',
        'created_at' => 'datetime',
    ];

    /* ===================== Relaciones ===================== */

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* ===================== Scopes ===================== */

    public function scopeDeAccion(Builder $query, ?string $accion): Builder
    {
        return $accion ? $query->where('accion', $accion) : $query;
    }

    /** Etiqueta legible, o el código crudo si la acción es desconocida. */
    public function etiqueta(): string
    {
        return self::ETIQUETAS[$this->accion] ?? $this->accion;
    }

    public function scopeDeActor(Builder $query, ?int $userId): Builder
    {
        return $userId ? $query->where('user_id', $userId) : $query;
    }
}
