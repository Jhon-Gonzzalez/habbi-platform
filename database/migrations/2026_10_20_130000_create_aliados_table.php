<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Anuncios de negocios locales, vendidos uno a uno.
         *
         * No es una red de publicidad: no hay script de terceros, no hay
         * cookies de rastreo y nadie fuera de este servidor se entera de
         * quién visita la página. El negocio paga un cupo fijo y aquí
         * queda su logo, su enlace y hasta cuándo lo pagó.
         *
         * Se cuentan los clics porque sin ese dato no hay forma de
         * justificar una renovación: «te mandamos 37 personas este mes»
         * es un argumento, «confía en mí» no lo es. Se cuenta el clic
         * (un número), no quién lo hizo.
         */
        Schema::create('aliados', function (Blueprint $tabla) {
            $tabla->id();

            $tabla->string('nombre', 120);
            $tabla->string('descripcion', 160);      // una línea, no un párrafo
            $tabla->string('url', 500);
            $tabla->string('logo_path')->nullable();
            $tabla->string('categoria', 60)->nullable();

            // Vigencia de lo pagado. Fuera de este rango no se muestra.
            $tabla->date('desde');
            $tabla->date('hasta');

            $tabla->unsignedInteger('monto')->default(0);
            $tabla->unsignedInteger('clics')->default(0);

            $tabla->boolean('activo')->default(true);
            $tabla->unsignedSmallInteger('orden')->default(0);

            $tabla->timestamps();

            $tabla->index(['activo', 'desde', 'hasta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aliados');
    }
};
