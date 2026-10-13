<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telefonos_vistos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alojamiento_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->index();

            // Para contar rápido cuántos pidió una persona en un rato.
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telefonos_vistos');
    }
};
