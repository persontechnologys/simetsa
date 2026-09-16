<?php
// database/factories/CredencialDiscapacidadFactory.php

namespace Database\Factories;

use App\Models\CredencialDiscapacidad;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CredencialDiscapacidad>
 */
class CredencialDiscapacidadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conductor_id'            => Conductor::factory(),
            'numero_conadis'          => strtoupper(fake()->bothify('##-????-CONADIS')),
            'nombre_beneficiario'     => fake()->name(),
            'porcentaje_discapacidad' => fake()->numberBetween(30, 100),
            'fecha_emision'           => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'fecha_vencimiento'       => null,
            'ruta_archivo'            => null,
            'estado'                  => CredencialDiscapacidad::ESTADO_PENDIENTE,
            'observaciones'           => null,
            'aprobada_por'            => null,
            'fecha_aprobacion'        => null,
        ];
    }

    /** Estado aprobada con usuario aprobador. */
    public function aprobada(): static
    {
        return $this->state([
            'estado'           => CredencialDiscapacidad::ESTADO_APROBADA,
            'aprobada_por'     => User::factory(),
            'fecha_aprobacion' => now(),
        ]);
    }

    /** Estado rechazada con motivo. */
    public function rechazada(): static
    {
        return $this->state([
            'estado'        => CredencialDiscapacidad::ESTADO_RECHAZADA,
            'observaciones' => 'Documentación incompleta o inválida.',
        ]);
    }
}
