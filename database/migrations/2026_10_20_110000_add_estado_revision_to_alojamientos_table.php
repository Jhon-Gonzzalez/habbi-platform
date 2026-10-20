<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado de revisión de la publicación.
     *
     * Es distinto de 'is_active', que lo controla el arrendador para pausar
     * su propio anuncio. Esto lo controla el administrador: un anuncio nace
     * «pendiente» y no se ve hasta que alguien lo aprueba.
     */
    public function up(): void
    {
        Schema::table('alojamientos', function (Blueprint $table) {
            $table->string('estado', 20)->default('pendiente')->after('is_active')->index();
            $table->text('motivo_rechazo')->nullable()->after('estado');
            $table->foreignId('revisada_por')->nullable()->after('motivo_rechazo')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('revisada_en')->nullable()->after('revisada_por');
        });

        /*
         * Las publicaciones que ya existían pasan a «aprobada».
         *
         * Dejarlas en «pendiente» las habría ocultado todas de golpe: unos
         * anuncios que llevan meses visibles desaparecerían del buscador sin
         * que su dueño hiciera nada. La revisión empieza a aplicar desde aquí.
         */
        DB::table('alojamientos')->update(['estado' => 'aprobada', 'revisada_en' => now()]);
    }

    public function down(): void
    {
        Schema::table('alojamientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revisada_por');
            $table->dropColumn(['estado', 'motivo_rechazo', 'revisada_en']);
        });
    }
};
