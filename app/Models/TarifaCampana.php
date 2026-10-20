<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Una tarifa: cuántos días dura una campaña y cuánto cuesta.
 *
 * Vive en la base de datos y no en config/ porque el administrador tiene
 * que poder cambiarla sin tocar código ni volver a desplegar. Los
 * precios de esto suben en temporada (enero y julio-agosto, cuando
 * empiezan los semestres) y bajan el resto del año; esa decisión es
 * suya, no de quien programa.
 */
class TarifaCampana extends Model
{
    use HasFactory;

    protected $table = 'tarifas_campana';

    protected $fillable = ['nombre', 'dias', 'precio', 'nota', 'activa', 'destacada', 'orden'];

    protected $casts = [
        'dias'      => 'integer',
        'precio'    => 'integer',
        'orden'     => 'integer',
        'activa'    => 'boolean',
        'destacada' => 'boolean',
    ];

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true)->orderBy('orden')->orderBy('dias');
    }

    public function precioFormateado(): string
    {
        return '$' . number_format($this->precio, 0, ',', '.');
    }

    /**
     * Lo que cuesta al día, para que el arrendador pueda comparar.
     *
     * Es el único número que deja ver si un plan largo sale mejor que
     * uno corto. Sin él, «$35.000 por 30 días» y «$20.000 por 15» se
     * comparan a ojo y mal.
     */
    public function precioPorDia(): int
    {
        return $this->dias > 0 ? (int) round($this->precio / $this->dias) : 0;
    }
}
