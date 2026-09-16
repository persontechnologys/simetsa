<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de Órdenes de Pago — Art. 28 Ordenanza SIMETSA.
 * Formaliza el cobro de una multa antes de que el conductor pueda pagar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infraccion_id')->constrained('infracciones')->cascadeOnDelete();
            $table->string('numero_orden')->unique();       // OP-0001
            $table->decimal('monto', 10, 2);
            $table->string('estado')->default('pendiente'); // cast → EstadoOrdenPago
            $table->timestamp('vence_at')->nullable();
            $table->foreignId('generada_por')->constrained('users');
            $table->text('motivo_anulacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_pago');
    }
};
