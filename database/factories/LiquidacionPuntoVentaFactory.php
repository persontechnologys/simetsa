<?php

namespace Database\Factories;

use App\Models\PuntoVenta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LiquidacionPuntoVenta>
 */
class LiquidacionPuntoVentaFactory extends Factory
{
    public function definition(): array
    {
        $bruto = $this->faker->randomFloat(2, 50, 5000);

        return [
            'punto_venta_id' => PuntoVenta::factory(),
            'periodo_mes'    => now()->startOfMonth()->toDateString(),
            'monto_bruto'    => $bruto,
            'porcentaje'     => 90.00,
            'monto_neto'     => round($bruto * 0.90, 2),
        ];
    }
}
