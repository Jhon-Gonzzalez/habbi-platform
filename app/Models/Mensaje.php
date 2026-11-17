<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mensaje extends Model
{
    use HasFactory;

    protected $table = 'mensajes';

    protected $fillable = [
        'conversacion_id', 'user_id', 'cuerpo', 'es_del_sistema', 'leido_en',
        'adjunto', 'adjunto_nombre', 'adjunto_tipo',
    ];

    /** Lo que se acepta: fotos del espacio y comprobantes en PDF. */
    public const TIPOS_ADJUNTO = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    public function tieneAdjunto(): bool
    {
        return $this->adjunto !== null;
    }

    /** Si es imagen se pinta; si no, se ofrece como enlace de descarga. */
    public function adjuntoEsImagen(): bool
    {
        return str_starts_with((string) $this->adjunto_tipo, 'image/');
    }

    protected $casts = [
        'es_del_sistema' => 'boolean',
        'leido_en'       => 'datetime',
    ];

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
