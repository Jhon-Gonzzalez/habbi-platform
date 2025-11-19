<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alojamiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'price',
        'price_period',
        'guests',
        'city',
        'neighborhood',
        'address',
        'description',
        'amenities',
        'phone',
        'cover_path',
        'photos',
    ];

    protected $casts = [
        'amenities' => 'array',
        'photos'    => 'array',
    ];

    // RELACIÓN CON USUARIO
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // RELACIÓN CON LAS RESEÑAS
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    // PROMEDIO DE CALIFICACIONES
    public function averageRating()
    {
        return round($this->ratings()->avg('rating') ?? 0, 1);
    }

    // TOTAL DE RESEÑAS
    public function ratingCount()
    {
        return $this->ratings()->count();
    }
}
