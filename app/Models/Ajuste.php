<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Ajustes que cambia el administrador desde el panel.
 *
 * Muy acotado a propósito: aquí viven los datos de cobro (a qué Nequi se
 * paga, a qué correo se escribe) y nada más. No es un cajón de sastre
 * para cualquier configuración. Lo que el administrador NO cambia sigue
 * en config/, donde se lee junto al código que lo usa.
 */
class Ajuste extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'clave';

    protected $keyType = 'string';

    protected $fillable = ['clave', 'valor'];

    /* Las claves que existen. Una constante por cada una para que un
       error de dedo no se convierta en un ajuste fantasma. */
    public const COBRO_NEQUI      = 'cobro.nequi';
    public const COBRO_DAVIPLATA  = 'cobro.daviplata';
    public const COBRO_CORREO     = 'cobro.correo';
    public const COBRO_INSTRUCCIONES = 'cobro.instrucciones';

    public const CLAVES = [
        self::COBRO_NEQUI         => 'Número de Nequi',
        self::COBRO_DAVIPLATA     => 'Número de Daviplata',
        self::COBRO_CORREO        => 'Correo para pedir una campaña',
        self::COBRO_INSTRUCCIONES => 'Instrucciones de pago',
    ];

    private const CACHE = 'habbi:ajustes';

    /** Todos los ajustes, en memoria: se leen en cada carga de la página. */
    public static function todos(): array
    {
        return Cache::rememberForever(self::CACHE, fn () => self::pluck('valor', 'clave')->all());
    }

    public static function obtener(string $clave, ?string $porDefecto = null): ?string
    {
        $valor = self::todos()[$clave] ?? null;

        // Un ajuste guardado en blanco vale lo mismo que no tenerlo: el
        // mismo problema que da env() con una clave vacía.
        return ($valor === null || $valor === '') ? $porDefecto : $valor;
    }

    public static function guardar(string $clave, ?string $valor): void
    {
        self::updateOrCreate(['clave' => $clave], ['valor' => $valor]);

        Cache::forget(self::CACHE);
    }
}
