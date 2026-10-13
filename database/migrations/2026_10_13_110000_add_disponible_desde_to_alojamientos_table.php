<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alojamientos', function (Blueprint $table) {
            // Desde cuándo se puede entrar. Nulo significa «ya»: es lo más
            // común y no tiene sentido obligar a escribir la fecha de hoy.
            $table->date('disponible_desde')->nullable()->after('guests')->index();
        });
    }

    public function down(): void
    {
        Schema::table('alojamientos', function (Blueprint $table) {
            $table->dropColumn('disponible_desde');
        });
    }
};
