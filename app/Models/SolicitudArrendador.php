<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Petición de un estudiante para convertirse en arrendador.
 *
 * Los documentos que adjunta viven en el disco 'private', nunca en public:
 * son cédulas y recibos, y no deben ser accesibles desde la web.
 */
class SolicitudArrendador extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_arrendador';

    public const PENDIENTE = 'pendiente';
    public const APROBADA  = 'aprobada';
    public const RECHAZADA = 'rechazada';

    public const ESTADOS = [
        self::PENDIENTE => 'Pendiente de revisión',
        self::APROBADA  => 'Aprobada',
        self::RECHAZADA => 'Rechazada',
    ];

    protected $fillable = [
        'user_id',
        'estado',
        'documento_path',
        'prueba_path',
        'telefono',
        'mensaje',
        'motivo_rechazo',
        'revisada_por',
        'revisada_en',
        'documentos_borrados_en',
    ];

    protected $casts = [
        'revisada_en'            => 'datetime',
        'documentos_borrados_en' => 'datetime',
    ];

    /* ===================== Relaciones ===================== */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisada_por');
    }

    /* ===================== Scopes ===================== */

    /** ¿Siguen estando los archivos, o ya se eliminaron al resolverla? */
    public function tieneDocumentos(): bool
    {
        return $this->documentos_borrados_en === null;
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::PENDIENTE);
    }

    /* ===================== Estado ===================== */

    public function estaPendiente(): bool
    {
        return $this->estado === self::PENDIENTE;
    }

    public function fueAprobada(): bool
    {
        return $this->estado === self::APROBADA;
    }

    public function fueRechazada(): bool
    {
        return $this->estado === self::RECHAZADA;
    }

    public function nombreEstado(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
