<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alojamientos', function (Blueprint $table) {
            // Universidad de referencia para calcular la ruta. Es opcional:
            // si no se indica, se usa la primera de la lista.
            $table->string('universidad_referencia', 160)->nullable()->after('address');

            // Coordenadas opcionales. Si existen, la ruta es exacta; si no,
            // se usa la dirección en texto y Google la resuelve.
            $table->decimal('lat', 10, 7)->nullable()->after('universidad_referencia');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });
    }

    public function down(): void
    {
        Schema::table('alojamientos', function (Blueprint $table) {
            $table->dropColumn(['universidad_referencia', 'lat', 'lng']);
        });
    }
};
