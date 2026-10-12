<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_arrendador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('estado', 20)->default('pendiente'); // pendiente|aprobada|rechazada

            // Rutas en el disco PRIVADO: estos archivos no deben ser
            // accesibles desde la web bajo ninguna circunstancia.
            $table->string('documento_path');
            $table->string('prueba_path');

            $table->string('telefono', 40);
            $table->text('mensaje');

            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('revisada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisada_en')->nullable();

            $table->timestamps();

            $table->index('estado');
            // Una solicitud pendiente por persona; las resueltas quedan de historial.
            $table->index(['user_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_arrendador');
    }
};
