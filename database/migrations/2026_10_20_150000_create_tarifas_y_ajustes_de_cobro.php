<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Las tarifas de campaña: cuántos días y cuánto cuesta.
         *
         * Estaban escritas en config/destacados.php, o sea que cambiar un
         * precio exigía editar un archivo, hacer commit y volver a
         * desplegar. Eso no sirve: los precios de esto tienen que subir
         * en temporada (enero, julio-agosto) y bajar el resto del año, y
         * quien decide eso es el administrador, no quien programa.
         *
         * El arrendador elige cuánto tiempo quiere estar y el precio sale
         * de aquí. No hay suscripción mensual: casi nadie pagaría meses
         * en los que sabe que nadie está buscando.
         */
        Schema::create('tarifas_campana', function (Blueprint $tabla) {
            $tabla->id();

            $tabla->string('nombre', 60);              // «Quincena», «Temporada»...
            $tabla->unsignedSmallInteger('dias');
            $tabla->unsignedInteger('precio');         // pesos, sin decimales
            $tabla->string('nota', 160)->nullable();   // «La más pedida»

            $tabla->boolean('activa')->default(true);
            $tabla->boolean('destacada')->default(false);   // la que se resalta
            $tabla->unsignedSmallInteger('orden')->default(0);

            $tabla->timestamps();

            $tabla->index(['activa', 'orden']);
        });

        /*
         * Ajustes que el administrador cambia desde el panel.
         *
         * Clave y valor, a propósito muy acotado: aquí viven los datos de
         * cobro (a qué Nequi se paga, a qué correo se escribe) y nada
         * más. No es un cajón de sastre para cualquier configuración: lo
         * que no cambia el administrador sigue en config/, donde se puede
         * leer junto al código que lo usa.
         */
        Schema::create('ajustes', function (Blueprint $tabla) {
            $tabla->string('clave', 60)->primary();
            $tabla->text('valor')->nullable();
            $tabla->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
        Schema::dropIfExists('tarifas_campana');
    }
};
