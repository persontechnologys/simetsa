<?php
// database/migrations/2026_06_06_154020_create_incidente_calles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de incidentes de calle reportados por el agente (Art. 38.m — Ordenanza SIMETSA).
 *
 * El agente debe reportar a ECU 911 ante accidentes, obstrucciones o conflictos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidentes_calle', function (Blueprint $table) {
            $table->id();

            $table->foreignId('turno_agente_id')
                ->constrained('turnos_agente')
                ->restrictOnDelete();

            // 'accidente' | 'obstruccion' | 'conflicto' | 'otro'
            $table->string('tipo', 20);

            $table->text('descripcion');

            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();

            $table->string('foto_evidencia')->nullable();

            $table->boolean('reportado_ecu911')->default(false);
            $table->timestamp('notificado_at')->nullable();

            $table->timestamps();

            $table->index(['turno_agente_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidentes_calle');
    }
};
