<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Alojamiento extends Model
{
    use HasFactory, SoftDeletes;

    /** Tipos de alojamiento admitidos en el formulario de publicación. */
    public const TIPOS = ['Habitación', 'Estudio', 'Apartamento', 'Loft', 'Suite', 'Apartaestudio'];

    /** Comodidades seleccionables. */
    public const COMODIDADES = [
        'Wifi', 'Lavadora', 'Parqueadero', 'Cocina', 'Pet Friendly',
        'Amoblado', 'Aire acondicionado', 'Baño privado', 'Servicios incluidos', 'Escritorio',
    ];

    /** Periodos de precio. */
    public const PERIODOS = ['mes', 'noche'];

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
        'is_active',
    ];

    protected $casts = [
        'amenities' => 'array',
        'photos'    => 'array',
        'is_active' => 'boolean',
        'price'     => 'integer',
        'guests'    => 'integer',
    ];

    /* ===================== Relaciones ===================== */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /* ===================== Scopes ===================== */

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Añade promedio y total de reseñas en una sola consulta (evita N+1). */
    public function scopeConResenas(Builder $query): Builder
    {
        return $query->withAvg('ratings', 'rating')->withCount('ratings');
    }

    /**
     * Aplica los filtros del buscador.
     *
     * @param  array<string, mixed>  $f
     */
    public function scopeFiltrar(Builder $query, array $f): Builder
    {
        $query
            ->when($f['q'] ?? null, function (Builder $sql, string $q) {
                $term = '%' . addcslashes($q, '%_\\') . '%';

                $sql->where(fn (Builder $w) => $w
                    ->where('title', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('neighborhood', 'like', $term));
            })
            ->when($f['type'] ?? null, fn (Builder $sql, $type) => $sql->where('type', $type))
            ->when($f['guests'] ?? null, fn (Builder $sql, $g) => $sql->where('guests', '>=', (int) $g))
            ->when($f['price_min'] ?? null, fn (Builder $sql, $p) => $sql->where('price', '>=', (int) $p))
            ->when($f['price_max'] ?? null, fn (Builder $sql, $p) => $sql->where('price', '<=', (int) $p))
            ->when(!empty($f['amenities']), function (Builder $sql) use ($f) {
                foreach ((array) $f['amenities'] as $amenidad) {
                    $sql->whereJsonContains('amenities', $amenidad);
                }
            });

        return match ($f['sort'] ?? null) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'rating'     => $query->orderByDesc('ratings_avg_rating'),
            'oldest'     => $query->oldest(),
            default      => $query->latest(),
        };
    }

    /* ===================== Accesores ===================== */

    /** Promedio de calificación redondeado a un decimal. */
    public function promedio(): float
    {
        $avg = $this->ratings_avg_rating ?? $this->ratings()->avg('rating');

        return round((float) $avg, 1);
    }

    /** Número de reseñas recibidas. */
    public function totalResenas(): int
    {
        return (int) ($this->ratings_count ?? $this->ratings()->count());
    }

    /** URL de la portada, con imagen por defecto si no hay foto. */
    public function coverUrl(): string
    {
        if ($this->cover_path && Storage::disk('public')->exists($this->cover_path)) {
            return Storage::disk('public')->url($this->cover_path);
        }

        return asset('assets/img/images/principal.jpg');
    }

    /**
     * Galería completa, con la portada primero y solo archivos existentes.
     *
     * @return array<int, array{path: string, url: string}>
     */
    public function galeria(): array
    {
        $rutas = collect($this->photos ?? []);

        if ($this->cover_path) {
            $rutas = $rutas->prepend($this->cover_path);
        }

        return $rutas
            ->unique()
            ->filter(fn ($p) => $p && Storage::disk('public')->exists($p))
            ->map(fn ($p) => ['path' => $p, 'url' => Storage::disk('public')->url($p)])
            ->values()
            ->all();
    }

    /** Precio formateado en pesos colombianos. */
    public function precioFormateado(): string
    {
        return '$' . number_format($this->price, 0, ',', '.');
    }

    /** Número de teléfono normalizado para enlaces wa.me / tel:. */
    public function telefonoLimpio(): ?string
    {
        return $this->phone ? preg_replace('/\D/', '', $this->phone) : null;
    }
}
