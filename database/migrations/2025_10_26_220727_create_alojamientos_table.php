<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('alojamientos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->string('type');
        $table->unsignedInteger('price');
        $table->string('price_period');
        $table->unsignedTinyInteger('guests');
        $table->string('city');
        $table->string('neighborhood')->nullable();
        $table->string('address')->nullable();
        $table->text('description');
        $table->json('amenities')->nullable();
        $table->string('phone')->nullable();
        $table->string('cover_path')->nullable();
        $table->json('photos')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alojamientos');
    }
};
