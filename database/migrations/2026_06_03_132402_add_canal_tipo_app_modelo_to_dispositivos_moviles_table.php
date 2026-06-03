<?php
// database/migrations/2026_06_03_132402_add_canal_tipo_app_modelo_to_dispositivos_moviles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extiende la tabla de dispositivos para la estrategia FCM directo (Fase 9.F).
 *
 * - canal: proveedor de push. 'fcm' para móvil nativo; 'web' para Web Push
 *   futuro (backoffice). Permite reutilizar el endpoint sin duplicar lógica.
 *
 * - tipo_app: rol de la sesión al registrar ('conductor' | 'agente').
 *   Facilita filtrar destinatarios de push sin join adicional con roles.
 *
 * - modelo_dispositivo: nombre del hardware (ej. "Pixel 6"). Útil para soporte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispositivos_moviles', function (Blueprint $table) {
            $table->string('canal', 20)->default('fcm')->after('plataforma');
            $table->string('tipo_app', 20)->nullable()->after('canal');
            $table->string('modelo_dispositivo', 100)->nullable()->after('tipo_app');
        });
    }

    public function down(): void
    {
        Schema::table('dispositivos_moviles', function (Blueprint $table) {
            $table->dropColumn(['canal', 'tipo_app', 'modelo_dispositivo']);
        });
    }
};
