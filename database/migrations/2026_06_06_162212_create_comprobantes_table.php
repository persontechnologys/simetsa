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
        // Art. 19 — Comprobante de pago (nota de venta interna, preparado para SRI futuro).
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->string('concepto_type');               // App\Models\Ticket | App\Models\Infraccion
            $table->unsignedBigInteger('concepto_id');
            $table->string('numero')->unique();            // CB-0001
            $table->decimal('monto', 10, 2);
            $table->timestamp('fecha_emision');
            $table->string('pdf_path')->nullable();        // ruta HTML imprimible generada
            $table->timestamps();

            $table->index(['concepto_type', 'concepto_id'], 'comprobantes_concepto_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
