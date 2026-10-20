<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Hasta cuándo está destacada la publicación.
         *
         * Una sola columna de fecha en vez de un booleano: así caduca sola
         * y no hace falta ninguna tarea que la apague. Si el valor está en
         * el pasado, deja de destacarse sin que nadie intervenga.
         */
        Schema::table('alojamientos', function (Blueprint $tabla) {
            $tabla->timestamp('destacado_hasta')->nullable()->after('is_active');
            $tabla->index(['destacado_hasta']);
        });

        /*
         * El historial de cobros.
         *
         * Va aparte de la columna anterior porque son dos cosas distintas:
         * una dice si HOY está destacada, la otra qué se pagó y cuándo. Sin
         * este historial no hay forma de cuadrar cuentas ni de saber quién
         * repite, que es lo único que dirá si el modelo de negocio funciona.
         *
         * No se guarda ningún dato de tarjeta ni de cuenta: el pago ocurre
         * fuera (Nequi, Daviplata, efectivo) y aquí solo queda la referencia
         * que el arrendador reporta, para poder cruzarla con el extracto.
         */
        Schema::create('destacados', function (Blueprint $tabla) {
            $tabla->id();

            $tabla->foreignId('alojamiento_id')->constrained('alojamientos')->cascadeOnDelete();

            // Quién lo activó en el panel. Si esa cuenta se borra, el cobro
            // debe seguir ahí: por eso nullOnDelete y no cascade.
            $tabla->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();

            $tabla->unsignedInteger('dias');
            $tabla->unsignedInteger('monto');            // en pesos, sin decimales
            $tabla->string('medio', 40);                 // nequi, daviplata, efectivo...
            $tabla->string('referencia', 120)->nullable();
            $tabla->text('nota')->nullable();

            $tabla->timestamp('empieza_en');
            $tabla->timestamp('termina_en');

            $tabla->timestamps();

            $tabla->index(['alojamiento_id', 'termina_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destacados');

        Schema::table('alojamientos', function (Blueprint $tabla) {
            $tabla->dropIndex(['destacado_hasta']);
            $tabla->dropColumn('destacado_hasta');
        });
    }
};
