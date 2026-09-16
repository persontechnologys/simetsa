<?php

// database/migrations/2026_06_06_173717_create_impugnacions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de impugnaciones de infracciones presentadas por conductores (Art. 17.f).
 *
 * Un conductor puede impugnar digitalmente una infracción en estado pendiente.
 * El comisario resuelve la impugnación (admitir, rechazar o resolver).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impugnaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('infraccion_id')
                ->constrained('infracciones')
                ->cascadeOnDelete();

            $table->foreignId('conductor_id')
                ->constrained('conductores')
                ->cascadeOnDelete();

            $table->text('motivo');

            // pendiente | admitida | rechazada | resuelta
            $table->string('estado')->default('pendiente');

            $table->text('resolucion')->nullable();

            $table->foreignId('resuelto_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('resuelto_at')->nullable();

            $table->timestamps();

            // Un conductor no puede impugnar dos veces la misma infracción
            $table->unique(['infraccion_id', 'conductor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impugnaciones');
    }
};
