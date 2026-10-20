<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Un negocio local que paga por aparecer en HABBI.
 *
 * La diferencia con una red de publicidad importa, y es toda la razón de
 * que esto exista como modelo propio: aquí el anuncio es un registro de
 * la base de datos, servido desde este mismo servidor. No se carga
 * ningún script ajeno, no hay cookies de rastreo y ningún tercero sabe
 * quién entró a buscar habitación.
 *
 * En una plataforma donde el producto es la confianza, meter el
 * rastreador de una red publicitaria sería cobrar unos pocos miles de
 * pesos a cambio de lo único que la hace valer algo.
 */
class Aliado extends Model
{
    use HasFactory;

    protected $table = 'aliados';

    public const CATEGORIAS = [
        'Comida', 'Papelería', 'Lavandería', 'Internet',
        'Muebles', 'Trasteos', 'Transporte', 'Otro',
    ];

    protected $fillable = [
        'nombre', 'descripcion', 'url', 'logo_path', 'categoria',
        'desde', 'hasta', 'monto', 'activo', 'orden',
    ];

    protected $casts = [
        'desde'  => 'date',
        'hasta'  => 'date',
        'monto'  => 'integer',
        'clics'  => 'integer',
        'orden'  => 'integer',
        'activo' => 'boolean',
    ];

    /** Los que se pueden mostrar hoy: activos y dentro de lo pagado. */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query
            ->where('activo', true)
            ->whereDate('desde', '<=', today())
            ->whereDate('hasta', '>=', today())
            ->orderBy('orden')
            ->orderBy('nombre');
    }

    public function estaVigente(): bool
    {
        return $this->activo
            && $this->desde->lte(today())
            && $this->hasta->gte(today());
    }

    public function diasRestantes(): int
    {
        return $this->hasta->isPast() ? 0 : (int) today()->diffInDays($this->hasta) + 1;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /** Las iniciales, para cuando el negocio no mandó logo. */
    public function iniciales(): string
    {
        $palabras = preg_split('/\s+/', trim($this->nombre)) ?: [];

        return mb_strtoupper(mb_substr($palabras[0] ?? '?', 0, 1)
            . (isset($palabras[1]) ? mb_substr($palabras[1], 0, 1) : ''));
    }

    public function montoFormateado(): string
    {
        return '$' . number_format($this->monto, 0, ',', '.');
    }
}
