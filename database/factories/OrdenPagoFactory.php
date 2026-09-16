<?php

namespace Database\Factories;

use App\Enums\EstadoOrdenPago;
use App\Models\Infraccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrdenPago>
 */
class OrdenPagoFactory extends Factory
{
    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'infraccion_id' => Infraccion::factory(),
            'numero_orden'  => 'OP-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
            'monto'         => $this->faker->randomFloat(2, 5, 500),
            'estado'        => EstadoOrdenPago::Pendiente,
            'vence_at'      => now()->addDays(30),
            'generada_por'  => User::factory(),
        ];
    }

    public function pagada(): static
    {
        return $this->state(['estado' => EstadoOrdenPago::Pagada]);
    }

    public function anulada(): static
    {
        return $this->state([
            'estado'           => EstadoOrdenPago::Anulada,
            'motivo_anulacion' => 'Anulada en tests.',
        ]);
    }
}
