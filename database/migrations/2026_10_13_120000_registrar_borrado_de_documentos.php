<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deja constancia de cuándo se borraron los documentos de una solicitud.
     *
     * Al resolverla, los archivos se eliminan del disco. La ruta se conserva
     * (es un hash aleatorio, no dice nada de nadie) y esta fecha permite a
     * la auditoría responder «sí, hubo cédula, se revisó y se eliminó el día
     * tal», que es lo que pide el principio de finalidad de la Ley 1581: los
     * datos se guardan mientras hagan falta para aquello que se pidieron, no
     * para siempre.
     */
    public function up(): void
    {
        Schema::table('solicitudes_arrendador', function (Blueprint $table) {
            $table->timestamp('documentos_borrados_en')->nullable()->after('prueba_path');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_arrendador', function (Blueprint $table) {
            $table->dropColumn('documentos_borrados_en');
        });
    }
};
