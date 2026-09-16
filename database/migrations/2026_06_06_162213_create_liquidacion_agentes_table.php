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
        // Art. 21 — Liquidación mensual: 60 % de lo recaudado al agente de parqueo.
        Schema::create('liquidaciones_agente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_parqueo_id')->constrained('agentes_parqueo')->cascadeOnDelete();
            $table->date('periodo_mes');                   // primer día del mes: 2026-06-01
            $table->decimal('monto_bruto', 10, 2);
            $table->decimal('porcentaje', 5, 2)->default(60.00);
            $table->decimal('monto_neto', 10, 2);
            $table->timestamps();

            $table->unique(['agente_parqueo_id', 'periodo_mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidaciones_agente');
    }
};
