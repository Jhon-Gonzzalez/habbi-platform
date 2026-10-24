<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Se va la tabla de tokens de API junto con Sanctum.
     *
     * HABBI no expone ninguna API: lo único que había era la ruta de
     * ejemplo que trae Laravel (`GET /api/user` con `auth:sanctum`), que
     * nadie usaba. Mantener el paquete instalado solo dejaba expuesto un
     * guard de autenticación de más, y justo ese guard es el de la
     * vulnerabilidad crítica CVE-2026-114509 (bypass de autenticación en
     * TokenGuard con credenciales que no son cadenas).
     *
     * La tabla siempre estuvo vacía. En una instalación nueva no llega a
     * crearse; en una que ya existía, esto la limpia.
     */
    public function up(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }

    public function down(): void
    {
        // No se recrea: volver a tenerla exigiría reinstalar Sanctum, y
        // eso es una decisión, no un paso atrás automático.
    }
};
