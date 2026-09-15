<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER  = 'user';

    protected $fillable = [
        'name',
        'email',
        'role',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    /* ===================== Relaciones ===================== */

    public function alojamientos(): HasMany
    {
        return $this->hasMany(Alojamiento::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /* ===================== Roles ===================== */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Iniciales para el avatar del navbar. */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
    }
}
