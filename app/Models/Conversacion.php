<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una conversación entre un estudiante y un arrendador sobre un alojamiento.
 *
 * POR QUÉ EXISTE
 * --------------
 * Antes el contacto se iba a WhatsApp. Eso tiene tres problemas: el
 * estudiante sale de la plataforma y no vuelve, no queda constancia de lo
 * que se prometió, y si hay una estafa no hay nada que mostrarle a nadie.
 *
 * Aquí la negociación ocurre dentro, y cuando las dos partes confirman el
 * acuerdo la publicación se actualiza sola.
 */
class Conversacion extends Model
{
    use HasFactory;

    protected $table = 'conversaciones';

    protected $fillable = [
        'alojamiento_id',
        'estudiante_id',
        'arrendador_id',
        'acuerdo_propuesto_por',
        'acuerdo_propuesto_en',
        'acuerdo_confirmado_en',
        'acuerdo_personas',
        'ultimo_mensaje_en',
    ];

    protected $casts = [
        'acuerdo_propuesto_en'  => 'datetime',
        'acuerdo_confirmado_en' => 'datetime',
        'ultimo_mensaje_en'     => 'datetime',
        'acuerdo_personas'      => 'integer',
    ];

    /* ===================== Relaciones ===================== */

    public function alojamiento(): BelongsTo
    {
        return $this->belongsTo(Alojamiento::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function arrendador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'arrendador_id');
    }

    public function proponente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acuerdo_propuesto_por');
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class)->oldest();
    }

    /* ===================== Participantes ===================== */

    public function participa(User $usuario): bool
    {
        return in_array($usuario->id, [$this->estudiante_id, $this->arrendador_id], true);
    }

    /** La otra persona, vista desde quien pregunta. */
    public function otro(User $usuario): ?User
    {
        return $usuario->id === $this->estudiante_id
            ? $this->arrendador
            : $this->estudiante;
    }

    /* ===================== Acuerdo ===================== */

    public function acuerdoConfirmado(): bool
    {
        return $this->acuerdo_confirmado_en !== null;
    }

    public function acuerdoPropuesto(): bool
    {
        return $this->acuerdo_propuesto_en !== null && ! $this->acuerdoConfirmado();
    }

    /**
     * ¿Le toca a esta persona confirmar?
     *
     * Solo a quien NO lo propuso. Si quien propone pudiera confirmar, un
     * arrendador daría por cerrado un trato que el estudiante no aceptó y
     * la publicación desaparecería del listado sin que nadie acordara nada.
     */
    public function puedeConfirmar(User $usuario): bool
    {
        return $this->acuerdoPropuesto()
            && $this->participa($usuario)
            && $this->acuerdo_propuesto_por !== $usuario->id;
    }

    /** Quien lo propuso puede retirarlo mientras el otro no haya respondido. */
    public function puedeRetirar(User $usuario): bool
    {
        return $this->acuerdoPropuesto() && $this->acuerdo_propuesto_por === $usuario->id;
    }

    /* ===================== Scopes ===================== */

    /** Las conversaciones en las que participa alguien, por actividad. */
    public function scopeDe(Builder $query, User $usuario): Builder
    {
        return $query
            ->where(fn ($q) => $q
                ->where('estudiante_id', $usuario->id)
                ->orWhere('arrendador_id', $usuario->id))
            ->orderByDesc('ultimo_mensaje_en');
    }

    /** Cuántos mensajes no ha leído esta persona. */
    public function sinLeerPara(User $usuario): int
    {
        return $this->mensajes()
            ->whereNot('user_id', $usuario->id)
            ->whereNull('leido_en')
            ->count();
    }
}
