<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alojamiento_id')->constrained()->cascadeOnDelete();

            $table->string('motivo', 40);
            $table->text('detalle')->nullable();

            $table->string('estado', 20)->default('abierto')->index();

            // Quién lo revisó y qué decidió.
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable();
            $table->text('nota_interna')->nullable();

            $table->timestamps();

            // Una persona no puede reportar el mismo alojamiento dos veces
            // mientras su reporte siga abierto; esto lo evita de raíz.
            $table->unique(['user_id', 'alojamiento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
