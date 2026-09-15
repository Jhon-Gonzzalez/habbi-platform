<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alojamientos', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('photos');
            $table->softDeletes();

            $table->index('city');
            $table->index('type');
            $table->index('price');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('alojamientos', function (Blueprint $table) {
            $table->dropIndex(['city']);
            $table->dropIndex(['type']);
            $table->dropIndex(['price']);
            $table->dropIndex(['is_active']);
            $table->dropSoftDeletes();
            $table->dropColumn('is_active');
        });
    }
};
