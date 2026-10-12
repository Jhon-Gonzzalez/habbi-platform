<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto_perfil')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('foto_perfil');
            $table->string('ciudad', 120)->nullable()->after('bio');
            $table->string('universidad', 160)->nullable()->after('ciudad');

            // Lo pone un administrador tras revisar documentos. No es editable
            // por el usuario: es justo lo que da valor al sello.
            $table->timestamp('verificado_en')->nullable()->after('universidad');
        });

        // El rol 'user' pasa a llamarse 'estudiante': ahora hay tres roles y
        // «usuario» ya no distingue a nadie.
        DB::table('users')->where('role', 'user')->update(['role' => 'estudiante']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'estudiante')->update(['role' => 'user']);
        DB::table('users')->where('role', 'arrendador')->update(['role' => 'user']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['foto_perfil', 'bio', 'ciudad', 'universidad', 'verificado_en']);
        });
    }
};
