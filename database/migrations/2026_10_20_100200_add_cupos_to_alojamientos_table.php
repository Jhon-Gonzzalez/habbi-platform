<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Plazas libres, que es distinto de la capacidad.
     *
     * 'guests' dice cuánta gente CABE (y por eso se filtra por él). Esto dice
     * cuántas plazas quedan por arrendar. Una habitación para dos personas
     * tiene guests=2 y puede tener 1 solo cupo: la habitación entera.
     *
     * Al confirmarse un acuerdo baja; cuando llega a cero, el alojamiento
     * deja de aparecer en el buscador.
     */
    public function up(): void
    {
        Schema::table('alojamientos', function (Blueprint $table) {
            $table->unsignedSmallInteger('cupos')->default(1)->after('guests')->index();
        });
    }

    public function down(): void
    {
        Schema::table('alojamientos', function (Blueprint $table) {
            $table->dropColumn('cupos');
        });
    }
};
