<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * «Destacado» pasa a llamarse «campaña».
     *
     * El arrendador no compra un adjetivo, compra un periodo de
     * promoción con fecha de inicio y de fin. «Campaña» dice eso; se
     * entiende que empieza, dura y termina, y permite hablar de
     * campañas de 7, 15 o 30 días sin que suene raro.
     *
     * La etiqueta que ve el estudiante en la tarjeta sigue diciendo
     * «Destacado», que es lo que él necesita entender: que ese anuncio
     * está pagado. Son dos públicos y dos palabras distintas a
     * propósito.
     */
    public function up(): void
    {
        Schema::rename('destacados', 'campanas');

        Schema::table('alojamientos', function (Blueprint $tabla) {
            // El índice se suelta ANTES de renombrar: después ya no se
            // encontraría por el nombre de la columna vieja.
            $tabla->dropIndex(['destacado_hasta']);
        });

        Schema::table('alojamientos', function (Blueprint $tabla) {
            $tabla->renameColumn('destacado_hasta', 'campana_hasta');
        });

        Schema::table('alojamientos', function (Blueprint $tabla) {
            $tabla->index(['campana_hasta']);
        });
    }

    public function down(): void
    {
        Schema::table('alojamientos', function (Blueprint $tabla) {
            $tabla->dropIndex(['campana_hasta']);
        });

        Schema::table('alojamientos', function (Blueprint $tabla) {
            $tabla->renameColumn('campana_hasta', 'destacado_hasta');
        });

        Schema::table('alojamientos', function (Blueprint $tabla) {
            $tabla->index(['destacado_hasta']);
        });

        Schema::rename('campanas', 'destacados');
    }
};
