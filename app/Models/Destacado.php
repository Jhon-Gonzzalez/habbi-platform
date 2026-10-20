<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un cobro por destacar una publicación.
 *
 * Es el registro contable, no el estado: si una publicación está
 * destacada hoy lo dice Alojamiento::destacado_hasta. Aquí queda qué se
 * cobró, a quién, cuándo y por cuántos días, que es lo que permite
 * cuadrar cuentas y ver quién repite.
 *
 * El pago ocurre FUERA de la plataforma (Nequi, Daviplata, efectivo). No
 * se guarda ningún dato bancario: solo la referencia que reporta el
 * arrendador, para poder cruzarla con el extracto.
 */
class Destacado extends Model
{
    use HasFactory;

    /** Los medios que se aceptan hoy. Cobro manual, sin pasarela. */
    public const MEDIOS = ['Nequi', 'Daviplata', 'Transferencia', 'Efectivo'];

    protected $fillable = [
        'alojamiento_id',
        'registrado_por',
        'dias',
        'monto',
        'medio',
        'referencia',
        'nota',
        'empieza_en',
        'termina_en',
    ];

    protected $casts = [
        'dias'        => 'integer',
        'monto'       => 'integer',
        'empieza_en'  => 'datetime',
        'termina_en'  => 'datetime',
    ];

    public function alojamiento(): BelongsTo
    {
        return $this->belongsTo(Alojamiento::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function montoFormateado(): string
    {
        return '$' . number_format($this->monto, 0, ',', '.');
    }

    public function sigueVigente(): bool
    {
        return $this->termina_en->isFuture();
    }
}
