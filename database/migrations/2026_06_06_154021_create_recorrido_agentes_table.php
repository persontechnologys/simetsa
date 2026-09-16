<?php
// database/migrations/2026_06_06_154019_create_recorrido_agentes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de puntos GPS del recorrido del agente durante su turno (Art. 38 — Ordenanza SIMETSA).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recorridos_agente', function (Blueprint $table) {
            $table->id();

            $table->foreignId('turno_agente_id')
                ->constrained('turnos_agente')
                ->cascadeOnDelete();

            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->timestamp('registrado_at');

            $table->timestamps();

            $table->index(['turno_agente_id', 'registrado_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recorridos_agente');
    }
};
