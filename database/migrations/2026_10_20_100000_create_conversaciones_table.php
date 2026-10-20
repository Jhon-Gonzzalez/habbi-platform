<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alojamiento_id')->constrained()->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('arrendador_id')->constrained('users')->cascadeOnDelete();

            /*
             * El acuerdo lo propone uno y lo confirma el OTRO. Dos campos y
             * no un booleano: si bastara con que una parte lo marcara, un
             * arrendador podría dar por cerrado un trato que el estudiante
             * no aceptó, y la publicación desaparecería del listado.
             */
            $table->foreignId('acuerdo_propuesto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acuerdo_propuesto_en')->nullable();
            $table->timestamp('acuerdo_confirmado_en')->nullable();
            $table->unsignedTinyInteger('acuerdo_personas')->default(1);

            // Para ordenar la bandeja por actividad sin recorrer los mensajes.
            $table->timestamp('ultimo_mensaje_en')->nullable()->index();

            $table->timestamps();

            // Una sola conversación por estudiante y alojamiento.
            $table->unique(['alojamiento_id', 'estudiante_id']);
            $table->index(['arrendador_id', 'ultimo_mensaje_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversaciones');
    }
};
