<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('ratings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('alojamiento_id')->constrained()->onDelete('cascade');
        $table->unsignedTinyInteger('rating'); // 1–5 estrellas
        $table->text('comment')->nullable();
        $table->timestamps();

        $table->unique(['user_id', 'alojamiento_id']); // un rating por usuario
    });
}

};
