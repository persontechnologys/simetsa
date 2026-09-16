<?php
// database/migrations/2026_06_06_154019_create_turno_agentes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de turnos del agente de parqueo (Art. 38 — Ordenanza SIMETSA).
 *
 * Registra el inicio y fin de cada turno para auditoría de asistencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos_agente', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agente_parqueo_id')
                ->constrained('agentes_parqueo')
                ->restrictOnDelete();

            $table->timestamp('inicio_at');
            $table->timestamp('fin_at')->nullable();

            // 'iniciado' | 'finalizado'
            $table->string('estado', 20)->default('iniciado');

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index(['agente_parqueo_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos_agente');
    }
};
