<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Art. 21 — Liquidación mensual: 90 % de lo recaudado al punto de venta.
        Schema::create('liquidaciones_punto_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('punto_venta_id')->constrained('puntos_venta')->cascadeOnDelete();
            $table->date('periodo_mes');                   // primer día del mes: 2026-06-01
            $table->decimal('monto_bruto', 10, 2);
            $table->decimal('porcentaje', 5, 2)->default(90.00);
            $table->decimal('monto_neto', 10, 2);
            $table->timestamps();

            $table->unique(['punto_venta_id', 'periodo_mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidaciones_punto_venta');
    }
};
