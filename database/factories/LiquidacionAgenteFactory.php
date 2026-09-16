<?php

namespace Database\Factories;

use App\Models\AgenteParqueo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LiquidacionAgente>
 */
class LiquidacionAgenteFactory extends Factory
{
    public function definition(): array
    {
        $bruto = $this->faker->randomFloat(2, 50, 2000);

        return [
            'agente_parqueo_id' => AgenteParqueo::factory(),
            'periodo_mes'       => now()->startOfMonth()->toDateString(),
            'monto_bruto'       => $bruto,
            'porcentaje'        => 60.00,
            'monto_neto'        => round($bruto * 0.60, 2),
        ];
    }
}
