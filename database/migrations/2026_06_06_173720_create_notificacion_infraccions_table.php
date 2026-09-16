<?php

// database/migrations/2026_06_06_173720_create_notificacion_infraccions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Boleta digital enviada al conductor cuando se registra una infracción a su vehículo.
 *
 * Se genera automáticamente en InfraccionService::registrar() cuando conductor_id no es null.
 * La constraint UNIQUE garantiza una sola boleta por infracción + conductor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_infraccion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('infraccion_id')
                ->constrained('infracciones')
                ->cascadeOnDelete();

            $table->foreignId('conductor_id')
                ->constrained('conductores')
                ->cascadeOnDelete();

            $table->timestamp('leida_at')->nullable();

            $table->boolean('enviada_push')->default(false);

            $table->timestamps();

            // Garantiza una sola boleta digital por infracción + conductor
            $table->unique(['infraccion_id', 'conductor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_infraccion');
    }
};
