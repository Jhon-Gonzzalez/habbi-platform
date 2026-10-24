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

    /*
    |--------------------------------------------------------------------------
    | Estado de revisión
    |--------------------------------------------------------------------------
    | Lo controla el ADMINISTRADOR y es distinto de is_active, que lo
    | controla el arrendador para pausar su propio anuncio. Una publicación
    | nace pendiente y no se ve en el buscador hasta que alguien la aprueba.
    */
    public const PENDIENTE = 'pendiente';
    public const APROBADA  = 'aprobada';
    public const RECHAZADA = 'rechazada';

    public const ESTADOS = [
        self::PENDIENTE => 'Pendiente de revisión',
        self::APROBADA  => 'Aprobada',
        self::RECHAZADA => 'Rechazada',
    ];

    /**
     * Universidades de Montería, para calcular la ruta desde el alojamiento.
     * La clave 'maps' es lo que se le pasa a Google Maps como destino: se usa
     * el nombre y la ciudad en vez de coordenadas porque Google los resuelve
     * mejor y el enlace sigue funcionando si el campus se muda.
     *
     * 'coords' es OPCIONAL y solo está en las que se pudieron verificar en
     * una fuente pública. El filtro por distancia solo ofrece esas: poner
     * coordenadas a ojo daría distancias falsas, y una distancia falsa es
     * peor que no ofrecer el filtro, porque el estudiante se fía de ella.
     *
     * Para añadir las que faltan (UPB, UCC y UNAD en Montería): abre el
     * campus en Google Maps, pulsa con el botón derecho sobre la entrada y
     * copia el par de números que aparece arriba del menú.
     */
    public const UNIVERSIDADES = [
        [
            'nombre' => 'Universidad de Córdoba',
            'corto'  => 'Unicórdoba',
            'maps'   => 'Universidad de Córdoba, Carrera 6 #76-103, Montería, Córdoba, Colombia',
            'coords' => [8.78778, -75.8578],   // Wikipedia (es y en), campus principal
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
            'coords' => [8.76710, -75.885112], // Wikipedia (es) y Wikidata, campus Elías Bechara Zainúm
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
        'estado',
        'motivo_rechazo',
        'revisada_por',
        'revisada_en',
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
        'campana_hasta',
    ];

    protected $casts = [
        'amenities' => 'array',
        'photos'    => 'array',
        'is_active' => 'boolean',
        'price'     => 'integer',
        'guests'    => 'integer',
        'cupos'     => 'integer',
        'revisada_en' => 'datetime',
        'campana_hasta' => 'datetime',
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
        return $query
            ->where('estado', self::APROBADA)
            ->where('is_active', true)
            ->where('cupos', '>', 0);
    }

    /**
     * Haversine en SQL, en su forma con asin.
     *
     * Con acos haría falta recortar el argumento a 1 por el redondeo del
     * coma flotante, y la función para recortarlo se llama distinto en cada
     * base de datos (LEAST en MySQL, MIN en SQLite): la consulta dejaría de
     * ser portable. Esta forma no lo necesita y da el mismo resultado.
     *
     * Espera tres parámetros, en este orden: latitud, latitud, longitud.
     */
    private const SQL_DISTANCIA = '(2 * 6371 * asin(sqrt('
        . 'power(sin(radians(? - lat) / 2), 2) '
        . '+ cos(radians(lat)) * cos(radians(?)) '
        . '* power(sin(radians(? - lng) / 2), 2)'
        . ')))';

    /**
     * Las que están a menos de X km de un punto, con la distancia calculada.
     *
     * Dos pasos a propósito:
     *
     * 1. Una caja alrededor del punto, con sumas y restas sobre lat/lng.
     *    Esto SÍ usa los índices y descarta de golpe casi todo.
     * 2. La fórmula del haversine sobre lo que queda, que da la distancia
     *    real sobre la esfera. Sola sería exacta pero obligaría a recorrer
     *    la tabla entera.
     *
     * Necesita que la base de datos traiga funciones trigonométricas:
     * MySQL siempre las tiene, y SQLite desde la versión 3.35 (2021).
     */
    public function scopeCercaDe(Builder $query, float $lat, float $lng, float $km): Builder
    {
        // Un grado de latitud son ~111 km en cualquier sitio. Uno de
        // longitud se estrecha al alejarse del ecuador, de ahí el coseno.
        $margenLat = $km / 111.045;
        $margenLng = $km / (111.045 * max(cos(deg2rad($lat)), 0.01));

        return $query
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereBetween('lat', [$lat - $margenLat, $lat + $margenLat])
            ->whereBetween('lng', [$lng - $margenLng, $lng + $margenLng])
            ->selectRaw('alojamientos.*, ' . self::SQL_DISTANCIA . ' as distancia_km', [$lat, $lat, $lng])
            // El recorte final va en WHERE y no en HAVING: SQLite rechaza un
            // HAVING sin GROUP BY, y MySQL lo acepta. En WHERE funciona en
            // las dos. La fórmula no se repite: es la misma constante.
            ->whereRaw(self::SQL_DISTANCIA . ' <= ?', [$lat, $lat, $lng, $km])
            ->orderBy('distancia_km');
    }

    /** Distancia en kilómetros hasta su universidad de referencia. */
    public function distanciaAUniversidad(): ?float
    {
        $uni = $this->universidad();

        if (! isset($uni['coords']) || $this->lat === null || $this->lng === null) {
            return null;
        }

        [$ulat, $ulng] = $uni['coords'];

        // Haversine, el mismo cálculo que hace la consulta.
        $a = sin(deg2rad($ulat - $this->lat) / 2) ** 2
            + cos(deg2rad($this->lat)) * cos(deg2rad($ulat))
            * sin(deg2rad($ulng - $this->lng) / 2) ** 2;

        return round(6371 * 2 * asin(min(1.0, sqrt($a))), 1);
    }

    /** Busca una universidad por nombre, solo entre las que tienen coordenadas. */
    public static function buscarUniversidad(?string $nombre): ?array
    {
        if (! $nombre) {
            return null;
        }

        foreach (self::universidadesConCoordenadas() as $u) {
            if ($u['nombre'] === $nombre) {
                return $u;
            }
        }

        return null;
    }

    /**
     * Los datos que necesita un pin del mapa, y nada más.
     *
     * Devuelve null si no hay coordenadas: el mapa descarta los puntos
     * vacíos, pero es más claro no mandárselos. Se incluye solo lo que se
     * va a pintar en la ficha emergente; la dirección exacta no, que el
     * pin ya la señala y el detalle está a un clic.
     */
    public function puntoMapa(): ?array
    {
        if ($this->lat === null || $this->lng === null) {
            return null;
        }

        return [
            'lat'    => (float) $this->lat,
            'lng'    => (float) $this->lng,
            'titulo' => $this->title,
            'precio' => $this->precioFormateado(),
            'url'    => route('alojamientos.show', $this),
        ];
    }

    /** Las universidades para las que sí se puede calcular distancia. */
    public static function universidadesConCoordenadas(): array
    {
        return array_values(array_filter(
            self::UNIVERSIDADES,
            fn (array $u) => isset($u['coords']),
        ));
    }

    /* ===================== Campañas de pago ===================== */

    /**
     * ¿Tiene una campaña activa ahora mismo?
     *
     * Se mira la fecha, no un interruptor: así caduca sola y no hace
     * falta ninguna tarea nocturna que vaya apagando campañas. Un
     * booleano habría exigido esa tarea, y el día que fallara se
     * quedarían publicaciones destacadas sin haberlas pagado.
     */
    public function tieneCampanaActiva(): bool
    {
        return $this->campana_hasta !== null && $this->campana_hasta->isFuture();
    }

    public function scopeConCampana(Builder $query): Builder
    {
        return $query->whereNotNull('campana_hasta')->where('campana_hasta', '>', now());
    }

    /**
     * Pone las destacadas arriba, sin excluir a nadie.
     *
     * CASE WHEN en vez de ordenar por la fecha: ordenar por
     * campana_hasta pondría primero al que compró más días, lo que
     * convertiría esto en una subasta. Así, todos los destacados van
     * arriba en igualdad y dentro de ese grupo manda el orden normal.
     */
    public function scopeConCampanaPrimero(Builder $query): Builder
    {
        return $query->orderByRaw(
            'CASE WHEN campana_hasta IS NOT NULL AND campana_hasta > ? THEN 0 ELSE 1 END',
            [now()],
        );
    }

    /** Cuántos días le quedan de campaña (0 si ya se le acabó). */
    public function diasDeCampanaRestantes(): int
    {
        return $this->tieneCampanaActiva()
            ? max(1, (int) ceil(now()->diffInHours($this->campana_hasta) / 24))
            : 0;
    }

    /**
     * Amplía la campaña en $dias a partir de hoy.
     *
     * Si ya tenía una activa, se SUMA a lo que le quedaba en vez de
     * reemplazarlo: quien paga dos quincenas seguidas no puede perder los
     * días que ya había comprado por renovar antes de tiempo.
     */
    public function activarCampana(int $dias): void
    {
        $desde = $this->tieneCampanaActiva() ? $this->campana_hasta : now();

        $this->update(['campana_hasta' => $desde->copy()->addDays($dias)]);
    }

    public function terminarCampana(): void
    {
        $this->update(['campana_hasta' => null]);
    }

    /** Las que esperan decisión del administrador. */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::PENDIENTE);
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

        // Distancia a una universidad concreta. Solo se aplica si la
        // universidad elegida tiene coordenadas verificadas; con las que no
        // las tienen, el filtro se ignora en vez de dar números inventados.
        $cerca = self::buscarUniversidad($f['universidad'] ?? null);

        if ($cerca && ! empty($f['km'])) {
            [$lat, $lng] = $cerca['coords'];
            $query->cercaDe($lat, $lng, (float) $f['km']);

            // La distancia manda sobre cualquier otro orden: quien filtra
            // por cercanía quiere lo más cerca primero.
            return $query;
        }

        /*
         * Las destacadas van arriba SOLO en el orden por defecto.
         *
         * Si el estudiante pidió expresamente «precio: menor a mayor», lo
         * que espera es la más barata primero. Colar ahí una publicación
         * pagada sería mentirle sobre lo que está mirando, y el día que lo
         * note deja de fiarse del buscador entero. El dinero no puede
         * comprar el orden que el usuario eligió a mano; solo la
         * visibilidad cuando no eligió ninguno.
         *
         * Lo mismo vale para el filtro por distancia, que sale antes de
         * aquí con su propio orden.
         */
        return match ($f['sort'] ?? null) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'rating'     => $query->orderByDesc('ratings_avg_rating'),
            'oldest'     => $query->oldest(),
            default      => $query->conCampanaPrimero()->latest(),
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

    /* ===================== Revisión ===================== */

    public function estaAprobada(): bool
    {
        return $this->estado === self::APROBADA;
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::PENDIENTE;
    }

    public function fueRechazada(): bool
    {
        return $this->estado === self::RECHAZADA;
    }

    /** ¿La ve alguien que no sea su dueño ni un administrador? */
    public function esVisibleAlPublico(): bool
    {
        return $this->estaAprobada() && $this->is_active && ! $this->estaArrendado();
    }

    public function nombreEstado(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    /** Sin plazas libres: alguien cerró el trato. */
    public function estaArrendado(): bool
    {
        return $this->cupos <= 0;
    }

    /**
     * El historial de campañas pagadas de esta publicación.
     *
     * NO se llama campanas() a propósito: una relación con el mismo
     * nombre que un scope lo tapa, y Eloquent resuelve al método real en
     * vez de al scope. Ya pasó una vez con destacados(), que reventaba
     * con «cannot be called statically» y dejó en rojo toda la suite de
     * administración.
     */
    /**
     * Quién aprobó o rechazó esta publicación.
     *
     * Antes la vista hacía User::find($alojamiento->revisada_por) a
     * mano. Como relación se puede precargar con el resto y, sobre
     * todo, la consulta deja de estar escondida en una plantilla.
     */
    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisada_por');
    }

    public function cobrosDeCampana(): HasMany
    {
        return $this->hasMany(Campana::class)->latest('termina_en');
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

    /**
     * Lo mismo, pero corto, para la tarjeta del listado.
     *
     * En el teléfono la rejilla va a dos columnas y cada tarjeta mide
     * unos 174 px. «Disponible desde el 24 de diciembre» se parte en
     * tres renglones y descuadra la fila entera; «Desde el 24 dic»
     * cabe en uno y dice lo mismo.
     */
    public function textoDisponibilidadCorto(): string
    {
        if ($this->estaDisponibleYa()) {
            return 'Disponible ya';
        }

        return 'Desde el ' . $this->disponible_desde->translatedFormat('j M');
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
