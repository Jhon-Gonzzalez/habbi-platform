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

    /**
     * Universidades de Montería, para calcular la ruta desde el alojamiento.
     * La clave 'maps' es lo que se le pasa a Google Maps como destino: se usa
     * el nombre y la ciudad en vez de coordenadas porque Google los resuelve
     * mejor y el enlace sigue funcionando si el campus se muda.
     */
    public const UNIVERSIDADES = [
        [
            'nombre' => 'Universidad de Córdoba',
            'corto'  => 'Unicórdoba',
            'maps'   => 'Universidad de Córdoba, Carrera 6 #76-103, Montería, Córdoba, Colombia',
        ],
        [
            'nombre' => 'Universidad Pontificia Bolivariana — Montería',
            'corto'  => 'UPB',
            'maps'   => 'Universidad Pontificia Bolivariana seccional Montería, Montería, Córdoba, Colombia',
        ],
        [
            'nombre' => 'Universidad del Sinú',
            'corto'  => 'Unisinú',
            'maps'   => 'Universidad del Sinú Elías Bechara Zainúm, Montería, Córdoba, Colombia',
        ],
        [
            'nombre' => 'Universidad Cooperativa de Colombia — Montería',
            'corto'  => 'UCC',
            'maps'   => 'Universidad Cooperativa de Colombia sede Montería, Montería, Córdoba, Colombia',
        ],
        [
            'nombre' => 'UNAD — CEAD Montería',
            'corto'  => 'UNAD',
            'maps'   => 'UNAD CEAD Montería, Montería, Córdoba, Colombia',
        ],
    ];

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'price',
        'price_period',
        'guests',
        'cupos',
        'disponible_desde',
        'city',
        'neighborhood',
        'address',
        'universidad_referencia',
        'lat',
        'lng',
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
        'cupos'     => 'integer',
        'lat'       => 'float',
        'disponible_desde' => 'date',
        'lng'       => 'float',
    ];

    /* ===================== Relaciones ===================== */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class);
    }

    /** Quienes guardaron este alojamiento. */
    public function guardadoPor(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favoritos')->withTimestamps();
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

    /**
     * Lo que se puede arrendar hoy: activo Y con plazas libres.
     *
     * Es distinto de «activo». Un alojamiento ya arrendado sigue siendo una
     * publicación válida —el arrendador puede reabrirla cuando se vaya el
     * inquilino— pero no tiene sentido enseñárselo a quien está buscando.
     */
    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('cupos', '>', 0);
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

        // Sin foto propia, una imagen generada para ESTE anuncio. Antes se
        // devolvía la portada del sitio, así que veinte tarjetas salían
        // idénticas y encima sugerían un alojamiento que nadie había visto.
        return route('alojamientos.marcador', $this);
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

    /* ===================== Disponibilidad ===================== */

    /** Sin plazas libres: alguien cerró el trato. */
    public function estaArrendado(): bool
    {
        return $this->cupos <= 0;
    }

    public function conversaciones(): HasMany
    {
        return $this->hasMany(Conversacion::class);
    }


    /** ¿Se puede entrar ya? Nulo o una fecha pasada significan que sí. */
    public function estaDisponibleYa(): bool
    {
        return $this->disponible_desde === null
            || $this->disponible_desde->isPast()
            || $this->disponible_desde->isToday();
    }

    /**
     * Texto corto para la tarjeta y el detalle.
     *
     * Una fecha pasada se trata como «ya»: el arrendador puso enero, llegó
     * febrero y nadie volvió a editarlo. Mostrar «disponible desde enero»
     * en marzo confunde más que ayudar.
     */
    public function textoDisponibilidad(): string
    {
        if ($this->estaDisponibleYa()) {
            return 'Disponible ya';
        }

        return 'Disponible desde el ' . $this->disponible_desde->translatedFormat('j \d\e F');
    }

    /** Solo los que se pueden ocupar antes de una fecha. */
    public function scopeDisponibleAntesDe(Builder $query, $fecha): Builder
    {
        return $query->where(fn ($q) => $q
            ->whereNull('disponible_desde')
            ->orWhere('disponible_desde', '<=', $fecha));
    }

    /* ===================== Ubicación y rutas ===================== */

    /**
     * Dirección en el formato que mejor entiende Google Maps. Si hay
     * coordenadas se usan esas, que son exactas; si no, el texto.
     */
    public function direccionParaMapa(): string
    {
        if ($this->lat && $this->lng) {
            return $this->lat . ',' . $this->lng;
        }

        return collect([$this->address, $this->neighborhood, $this->city, 'Colombia'])
            ->filter()
            ->implode(', ');
    }

    /** La universidad de referencia, o la primera de la lista por defecto. */
    public function universidad(): array
    {
        foreach (self::UNIVERSIDADES as $u) {
            if ($u['nombre'] === $this->universidad_referencia) {
                return $u;
            }
        }

        return self::UNIVERSIDADES[0];
    }

    /** Enlace que abre Google Maps mostrando dónde está el alojamiento. */
    public function urlMapa(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='
            . rawurlencode($this->direccionParaMapa());
    }

    /**
     * Enlace que abre Google Maps con la RUTA desde el alojamiento hasta la
     * universidad. travelmode=walking porque para un estudiante lo relevante
     * es cuánto se camina; Google deja cambiarlo con un toque.
     */
    public function urlRuta(?string $universidad = null): string
    {
        $destino = $universidad ?? $this->universidad()['maps'];

        return 'https://www.google.com/maps/dir/?api=1'
            . '&origin=' . rawurlencode($this->direccionParaMapa())
            . '&destination=' . rawurlencode($destino)
            . '&travelmode=walking';
    }

    /** Número de teléfono normalizado para enlaces wa.me / tel:. */
    public function telefonoLimpio(): ?string
    {
        return $this->phone ? preg_replace('/\D/', '', $this->phone) : null;
    }
}
