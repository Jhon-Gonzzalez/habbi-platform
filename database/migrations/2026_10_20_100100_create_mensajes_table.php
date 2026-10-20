<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('conversaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->text('cuerpo');

            // Para los mensajes que escribe el sistema («acuerdo confirmado»),
            // que se ven distintos y no los firma ninguna persona.
            $table->boolean('es_del_sistema')->default(false);

            $table->timestamp('leido_en')->nullable();
            $table->timestamps();

            $table->index(['conversacion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
