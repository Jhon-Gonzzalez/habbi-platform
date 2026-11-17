<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archivos adjuntos en el chat.
 *
 * Hacen falta de verdad: un arrendador manda la foto del cuarto que falta
 * en la publicación, un estudiante manda el comprobante del depósito.
 * Hacerlo por fuera, por WhatsApp, es justo lo que la plataforma quería
 * evitar, porque ahí no queda constancia de nada.
 *
 * Los archivos van al disco «private», fuera de public/. Una conversación
 * es privada: ni siquiera un administrador puede leerla, y un adjunto en
 * una URL pública la abriría a cualquiera que la adivine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensajes', function (Blueprint $tabla) {
            $tabla->string('adjunto')->nullable()->after('cuerpo');

            // El nombre original, para que al descargarlo no salga con el
            // identificador aleatorio con que se guardó.
            $tabla->string('adjunto_nombre')->nullable()->after('adjunto');

            // Se guarda el tipo detectado al subirlo, no el que declaró el
            // navegador: así la vista sabe si pintar una miniatura o un
            // enlace sin tener que abrir el archivo en cada carga.
            $tabla->string('adjunto_tipo', 60)->nullable()->after('adjunto_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('mensajes', function (Blueprint $tabla) {
            $tabla->dropColumn(['adjunto', 'adjunto_nombre', 'adjunto_tipo']);
        });
    }
};
