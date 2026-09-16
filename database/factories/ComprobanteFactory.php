<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comprobante>
 */
class ComprobanteFactory extends Factory
{
    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'concepto_type' => (Ticket::class),
            'concepto_id'   => Ticket::factory(),
            'numero'        => 'CB-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
            'monto'         => $this->faker->randomFloat(2, 1, 200),
            'fecha_emision' => now(),
        ];
    }
}
