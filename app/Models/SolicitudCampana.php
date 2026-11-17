<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lo que pide un arrendador cuando quiere promocionar su publicación.
 *
 * Es una bandeja de entrada, no un estado: que una publicación esté en
 * campaña HOY lo dice Alojamiento::campana_hasta, y el cobro queda en
 * Campana. Aquí solo vive la petición y cómo se resolvió.
 *
 * El importe y el nombre del plan se copian al crearla. La tarifa puede
 * subir de precio mañana, y una solicitud de ayer tiene que seguir
 * diciendo lo que se acordó ayer.
 */
class SolicitudCampana extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_campana';

    public const PENDIENTE = 'pendiente';
    public const APROBADA  = 'aprobada';
    public const RECHAZADA = 'rechazada';

    protected $fillable = [
        'solicitada_por', 'tarifa_id', 'nombre_tarifa', 'dias', 'monto',
        'medio', 'referencia', 'comprobante', 'nota',
        'estado', 'respuesta', 'resuelta_por', 'resuelta_en',
    ];

    protected function casts(): array
    {
        return [
            'dias'        => 'integer',
            'monto'       => 'integer',
            'resuelta_en' => 'datetime',
        ];
    }

    /* ===================== Relaciones ===================== */

    public function alojamiento(): BelongsTo
    {
        return $this->belongsTo(Alojamiento::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitada_por');
    }

    public function resolutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelta_por');
    }

    public function tarifa(): BelongsTo
    {
        return $this->belongsTo(TarifaCampana::class, 'tarifa_id');
    }

    /* ===================== Consultas ===================== */

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::PENDIENTE);
    }

    /* ===================== Estado ===================== */

    public function estaPendiente(): bool
    {
        return $this->estado === self::PENDIENTE;
    }

    public function montoFormateado(): string
    {
        return '$' . number_format($this->monto, 0, ',', '.');
    }

    /** Para pintar el estado sin repetir el match en cada vista. */
    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            self::APROBADA  => 'Aprobada',
            self::RECHAZADA => 'Rechazada',
            default         => 'Pendiente de verificar el pago',
        };
    }
}
