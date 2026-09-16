<?php
// Migra credencial CONADIS de vehiculo_id → conductor_id (Art. 26).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credenciales_discapacidad', function (Blueprint $table) {
            $table->foreignId('conductor_id')
                ->after('id')
                ->nullable()
                ->constrained('conductores')
                ->cascadeOnDelete();
        });

        // Poblar conductor_id desde el vehículo para registros existentes (sintaxis PostgreSQL)
        DB::statement('
            UPDATE credenciales_discapacidad
            SET conductor_id = v.conductor_id
            FROM vehiculos v
            WHERE v.id = credenciales_discapacidad.vehiculo_id
        ');

        Schema::table('credenciales_discapacidad', function (Blueprint $table) {
            $table->dropForeign(['vehiculo_id']);
            $table->dropColumn('vehiculo_id');

            // conductor_id ya no debe ser nullable
            $table->foreignId('conductor_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('credenciales_discapacidad', function (Blueprint $table) {
            $table->foreignId('vehiculo_id')->nullable()->constrained('vehiculos')->cascadeOnDelete();
        });

        Schema::table('credenciales_discapacidad', function (Blueprint $table) {
            $table->dropForeign(['conductor_id']);
            $table->dropColumn('conductor_id');
        });
    }
};
