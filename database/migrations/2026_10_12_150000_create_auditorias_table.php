<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();

            // Quién. Si la cuenta se borra, la fila se queda con el nombre
            // guardado: el registro no se puede limpiar borrándose a sí mismo.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_nombre', 120);
            $table->string('actor_correo', 160);

            // Qué.
            $table->string('accion', 60)->index();
            $table->string('descripcion', 255);

            // Sobre qué. Sin clave foránea a propósito: el registro debe
            // sobrevivir al borrado de aquello que describe.
            $table->string('objeto_tipo', 60)->nullable();
            $table->unsignedBigInteger('objeto_id')->nullable();
            $table->string('objeto_nombre', 255)->nullable();

            // Contexto.
            $table->json('datos')->nullable();
            $table->string('ip', 45)->nullable();

            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
