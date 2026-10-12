<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un aviso de que algo anda mal con una publicación.
 *
 * Lo manda un estudiante y lo revisa un administrador. No oculta la
 * publicación por sí solo: un reporte es una señal, no una sentencia, y
 * bastaría con un competidor malintencionado para tumbar anuncios buenos.
 */
class Reporte extends Model
{
    use HasFactory;

    public const ESTADO_ABIERTO  = 'abierto';
    public const ESTADO_ATENDIDO = 'atendido';
    public const ESTADO_DESCARTADO = 'descartado';

    /** Motivos que puede elegir quien reporta. */
    public const MOTIVOS = [
        'inexistente'   => 'El alojamiento no existe o la dirección es falsa',
        'precio'        => 'El precio real es distinto al publicado',
        'fotos'         => 'Las fotos no corresponden al alojamiento',
        'contacto'      => 'El teléfono no responde o es de otra persona',
        'estafa'        => 'Me pidieron dinero por adelantado o parece una estafa',
        'discriminacion' => 'Contiene trato discriminatorio',
        'duplicado'     => 'Está publicado varias veces',
        'otro'          => 'Otro motivo',
    ];

    protected $fillable = [
        'user_id',
        'alojamiento_id',
        'motivo',
        'detalle',
        'estado',
        'revisado_por',
        'revisado_en',
        'nota_interna',
    ];

    protected $casts = [
        'revisado_en' => 'datetime',
    ];

    /* ===================== Relaciones ===================== */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alojamiento(): BelongsTo
    {
        return $this->belongsTo(Alojamiento::class);
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    /* ===================== Scopes ===================== */

    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ABIERTO);
    }

    /* ===================== Ayudas ===================== */

    public function estaAbierto(): bool
    {
        return $this->estado === self::ESTADO_ABIERTO;
    }

    /** Texto del motivo, o el código crudo si alguien lo cambió en la base. */
    public function motivoTexto(): string
    {
        return self::MOTIVOS[$this->motivo] ?? $this->motivo;
    }
}
