<?php

namespace App\Services;

use App\Models\Alojamiento;
use App\Models\Conversacion;
use App\Models\Rating;
use App\Models\TelefonoVisto;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Métricas del panel para el reporte (RF-11, CU-08).
 *
 * Todo se calcula aquí y no en la vista, para que la pantalla y el PDF
 * enseñen exactamente los mismos números: si cada uno hiciera sus propias
 * consultas, acabarían discrepando y el reporte dejaría de servir.
 */
class ReporteEstadisticas
{
    public function __construct(
        public readonly CarbonImmutable $desde,
        public readonly CarbonImmutable $hasta,
    ) {
    }

    public static function entre(?string $desde, ?string $hasta): self
    {
        $fin = $hasta ? CarbonImmutable::parse($hasta) : CarbonImmutable::now();

        // Por defecto, el último mes: es el rango que se mira casi siempre.
        $inicio = $desde ? CarbonImmutable::parse($desde) : $fin->subDays(29);

        // Si alguien invierte las fechas, se enderezan en vez de devolver
        // un informe vacío que parecería un error de la plataforma.
        if ($inicio->greaterThan($fin)) {
            [$inicio, $fin] = [$fin, $inicio];
        }

        return new self($inicio->startOfDay(), $fin->endOfDay());
    }

    public function dias(): int
    {
        return $this->desde->diffInDays($this->hasta) + 1;
    }

    /** @return array<string, int> */
    public function usuarios(): array
    {
        $nuevos = User::whereBetween('created_at', [$this->desde, $this->hasta]);

        return [
            'total'       => User::count(),
            'nuevos'      => (clone $nuevos)->count(),
            'estudiantes' => User::where('role', User::ROLE_ESTUDIANTE)->count(),
            'arrendadores' => User::where('role', User::ROLE_ARRENDADOR)->count(),
            'verificados' => User::whereNotNull('verificado_en')->count(),
        ];
    }

    /** @return array<string, int> */
    public function publicaciones(): array
    {
        return [
            'total'      => Alojamiento::count(),
            'nuevas'     => Alojamiento::whereBetween('created_at', [$this->desde, $this->hasta])->count(),
            'activas'    => Alojamiento::disponibles()->count(),
            'pendientes' => Alojamiento::pendientes()->count(),
            'rechazadas' => Alojamiento::where('estado', Alojamiento::RECHAZADA)->count(),
            'arrendadas' => Alojamiento::where('cupos', 0)->count(),
        ];
    }

    /** @return array<string, int|float> */
    public function actividad(): array
    {
        return [
            'resenas'      => Rating::whereBetween('created_at', [$this->desde, $this->hasta])->count(),
            'promedio'     => round((float) Rating::avg('rating'), 1),
            'conversaciones' => Conversacion::whereBetween('created_at', [$this->desde, $this->hasta])->count(),
            'acuerdos'     => Conversacion::whereBetween('acuerdo_confirmado_en', [$this->desde, $this->hasta])->count(),
            'telefonos'    => TelefonoVisto::whereBetween('created_at', [$this->desde, $this->hasta])->count(),
        ];
    }

    /** Altas de usuarios por día, para la gráfica. @return Collection<string, int> */
    public function registrosPorDia(): Collection
    {
        return $this->porDia(User::query());
    }

    /** Publicaciones creadas por día. @return Collection<string, int> */
    public function publicacionesPorDia(): Collection
    {
        return $this->porDia(Alojamiento::query());
    }

    /** Oferta por barrio, de mayor a menor. @return Collection<string, int> */
    public function porBarrio(): Collection
    {
        return Alojamiento::query()
            ->disponibles()
            ->whereNotNull('neighborhood')
            ->selectRaw('neighborhood, count(*) as total')
            ->groupBy('neighborhood')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'neighborhood');
    }

    /** Precio medio por tipo de alojamiento. @return Collection<string, int> */
    public function precioMedioPorTipo(): Collection
    {
        return Alojamiento::query()
            ->disponibles()
            ->where('price_period', 'mes')
            ->selectRaw('type, round(avg(price)) as medio')
            ->groupBy('type')
            ->orderByDesc('medio')
            ->pluck('medio', 'type');
    }

    /**
     * Cuenta por día rellenando los días sin actividad con cero.
     *
     * Sin rellenar, una gráfica de barras se comprime y un hueco de tres
     * días sin registros parece un día normal.
     *
     * @return Collection<string, int>
     */
    private function porDia($consulta): Collection
    {
        $crudo = $consulta
            ->whereBetween('created_at', [$this->desde, $this->hasta])
            ->selectRaw('date(created_at) as dia, count(*) as total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $serie = collect();

        for ($f = $this->desde; $f->lessThanOrEqualTo($this->hasta); $f = $f->addDay()) {
            $clave = $f->format('Y-m-d');
            $serie->put($clave, (int) ($crudo[$clave] ?? 0));
        }

        return $serie;
    }

    /** Lo que se usa como título del PDF y del archivo. */
    public function titulo(): string
    {
        return 'HABBI · informe del ' . $this->desde->format('d/m/Y')
            . ' al ' . $this->hasta->format('d/m/Y');
    }

    public function nombreArchivo(): string
    {
        return 'habbi-informe-' . $this->desde->format('Ymd') . '-' . $this->hasta->format('Ymd') . '.pdf';
    }
}
