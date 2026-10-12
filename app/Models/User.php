<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    | Registrarse te hace ESTUDIANTE: puedes buscar, reseñar y guardar
    | favoritos. Para publicar hace falta ser ARRENDADOR, y a eso solo se
    | llega pasando una verificación que aprueba un administrador.
    */
    public const ROLE_ESTUDIANTE = 'estudiante';
    public const ROLE_ARRENDADOR = 'arrendador';
    public const ROLE_ADMIN      = 'admin';

    public const ROLES = [
        self::ROLE_ESTUDIANTE => 'Estudiante',
        self::ROLE_ARRENDADOR => 'Arrendador',
        self::ROLE_ADMIN      => 'Administrador',
    ];

    protected $fillable = [
        'name',
        'email',
        'role',
        'phone',
        'password',
        'foto_perfil',
        'bio',
        'ciudad',
        'universidad',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'verificado_en'     => 'datetime',
        'password'          => 'hashed',
    ];

    /* ===================== Relaciones ===================== */

    public function alojamientos(): HasMany
    {
        return $this->hasMany(Alojamiento::class);
    }

    /** Alojamientos que esta persona guardó para volver a verlos. */
    public function favoritos(): BelongsToMany
    {
        return $this->belongsToMany(Alojamiento::class, 'favoritos')
            ->withTimestamps()
            ->orderByPivot('created_at', 'desc');
    }

    /** ¿Tiene guardado este alojamiento? */
    public function tieneEnFavoritos(Alojamiento|int $alojamiento): bool
    {
        $id = $alojamiento instanceof Alojamiento ? $alojamiento->id : $alojamiento;

        // relationLoaded evita una consulta por tarjeta cuando el listado ya
        // cargó los favoritos del usuario.
        if ($this->relationLoaded('favoritos')) {
            return $this->favoritos->contains('id', $id);
        }

        return $this->favoritos()->whereKey($id)->exists();
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(SolicitudArrendador::class);
    }

    /** La última solicitud enviada, sea cual sea su estado. */
    public function ultimaSolicitud(): HasOne
    {
        return $this->hasOne(SolicitudArrendador::class)->latestOfMany();
    }

    /* ===================== Roles ===================== */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function esArrendador(): bool
    {
        return $this->role === self::ROLE_ARRENDADOR;
    }

    public function esEstudiante(): bool
    {
        return $this->role === self::ROLE_ESTUDIANTE;
    }

    /** Quién tiene permitido publicar alojamientos. */
    public function puedePublicar(): bool
    {
        return $this->esArrendador() || $this->isAdmin();
    }

    public function estaVerificado(): bool
    {
        return $this->verificado_en !== null;
    }

    public function nombreRol(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /** ¿Tiene una solicitud esperando revisión? */
    public function tieneSolicitudPendiente(): bool
    {
        return $this->solicitudes()
            ->where('estado', SolicitudArrendador::PENDIENTE)
            ->exists();
    }

    /* ===================== Perfil ===================== */

    /** Iniciales para el avatar cuando no hay foto. */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
    }

    /** URL de la foto de perfil, o null si no tiene (se usa el avatar). */
    public function fotoUrl(): ?string
    {
        if ($this->foto_perfil && Storage::disk('public')->exists($this->foto_perfil)) {
            return Storage::disk('public')->url($this->foto_perfil);
        }

        return null;
    }

    /** Promedio de las calificaciones de todos sus alojamientos. */
    public function promedioComoArrendador(): float
    {
        $avg = Rating::whereIn('alojamiento_id', $this->alojamientos()->select('id'))->avg('rating');

        return round((float) $avg, 1);
    }

    public function totalResenasRecibidas(): int
    {
        return Rating::whereIn('alojamiento_id', $this->alojamientos()->select('id'))->count();
    }
}
