<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de quién pidió ver el teléfono de un alojamiento.
 *
 * POR QUÉ EXISTE
 * --------------
 * Antes el teléfono salía impreso en la página para cualquiera con sesión.
 * Como registrarse es gratis, bastaba con crear una cuenta y recorrer el
 * listado para llevarse la lista de contactos de todos los arrendadores.
 *
 * Ahora hay que pedirlo, y cada petición deja rastro. Un estudiante
 * buscando cuarto pide diez o quince y no nota nada; quien pide cien en
 * cinco minutos queda a la vista.
 */
class TelefonoVisto extends Model
{
    use HasFactory;

    protected $table = 'telefonos_vistos';

    /** Solo importa cuándo se pidió; nada de esto se edita después. */
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'alojamiento_id', 'ip'];

    protected $casts = ['created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alojamiento(): BelongsTo
    {
        return $this->belongsTo(Alojamiento::class);
    }
}
