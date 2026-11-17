<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las solicitudes de campaña que mandan los arrendadores.
 *
 * Antes el arrendador tenía que escribir un correo diciendo qué quería, y
 * el administrador registraba el cobro a mano. Se perdían solicitudes y no
 * quedaba rastro de quién pidió qué.
 *
 * Esta tabla es la bandeja de entrada: el arrendador elige plan desde la
 * página, queda aquí en «pendiente», el administrador comprueba el pago y
 * la aprueba. Aprobar es lo único que activa la campaña.
 *
 * El pago sigue ocurriendo FUERA (Nequi, Daviplata, efectivo): no se
 * guarda ningún dato bancario, solo la referencia que reporta el
 * arrendador para poder cruzarla con el extracto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_campana', function (Blueprint $tabla) {
            $tabla->id();

            $tabla->foreignId('alojamiento_id')->constrained('alojamientos')->cascadeOnDelete();
            $tabla->foreignId('solicitada_por')->constrained('users')->cascadeOnDelete();

            // La tarifa puede borrarse o cambiar de precio mañana, así que
            // se guarda una copia de lo que se pidió HOY. Si no, una
            // solicitud vieja mostraría un precio que nadie acordó.
            $tabla->foreignId('tarifa_id')->nullable()->constrained('tarifas_campana')->nullOnDelete();
            $tabla->string('nombre_tarifa');
            $tabla->unsignedSmallInteger('dias');
            $tabla->unsignedInteger('monto');

            $tabla->string('medio');
            $tabla->string('referencia')->nullable();

            // El comprobante del pago. Va al disco «private», fuera de
            // public/, porque es un documento bancario de una persona: no
            // puede quedar en una URL que adivine cualquiera.
            $tabla->string('comprobante')->nullable();
            $tabla->text('nota')->nullable();

            $tabla->string('estado')->default('pendiente');
            $tabla->text('respuesta')->nullable();
            $tabla->foreignId('resuelta_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('resuelta_en')->nullable();

            $tabla->timestamps();

            // El panel pide siempre las pendientes, más antiguas primero.
            $tabla->index(['estado', 'created_at']);
            $tabla->index('alojamiento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_campana');
    }
};
