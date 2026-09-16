<?php
// database/factories/AgenteParqueoFactory.php

namespace Database\Factories;

use App\Models\AgenteParqueo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para AgenteParqueo.
 *
 * Genera agentes con campos realistas para usar en tests sin
 * tener que crear manualmente cada registro.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgenteParqueo>
 */
class AgenteParqueoFactory extends Factory
{
    protected $model = AgenteParqueo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'codigo'                   => 'AG-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'user_id'                  => User::factory(),
            'solicitud_agente_id'      => null,
            'numero_credencial'        => 'CRED-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'numero_oficio_comisario'  => null,
            'carta_compromiso_firmada' => true,
            'fecha_autorizacion'       => now()->toDateString(),
            'estado'                   => AgenteParqueo::ESTADO_ACTIVO,
        ];
    }

    /**
     * Estado suspendido (Art. 40 — sanción administrativa).
     */
    public function suspendido(): static
    {
        return $this->state(['estado' => AgenteParqueo::ESTADO_SUSPENDIDO]);
    }

    /**
     * Estado terminado (Art. 40.c — tres amonestaciones).
     */
    public function terminado(): static
    {
        return $this->state(['estado' => AgenteParqueo::ESTADO_TERMINADO]);
    }
}
