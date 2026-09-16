<?php

// app/Models/LiquidacionAgente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Liquidación mensual de un Agente de Parqueo (Art. 21 — 60 % de lo recaudado).
 *
 * @property int            $id
 * @property int            $agente_parqueo_id
 * @property \Carbon\Carbon $periodo_mes   Primer día del mes (ej. 2026-06-01).
 * @property float          $monto_bruto
 * @property float          $porcentaje    60 por defecto.
 * @property float          $monto_neto
 */
class LiquidacionAgente extends Model
{
    /** @use HasFactory<\Database\Factories\LiquidacionAgenteFactory> */
    use HasFactory;

    /** @var string Nombre explícito (Laravel inferiría 'liquidacion_agentes'). */
    protected $table = 'liquidaciones_agente';

    protected $fillable = [
        'agente_parqueo_id',
        'periodo_mes',
        'monto_bruto',
        'porcentaje',
        'monto_neto',
    ];

    protected function casts(): array
    {
        return [
            'periodo_mes'  => 'date',
            'monto_bruto'  => 'decimal:2',
            'porcentaje'   => 'decimal:2',
            'monto_neto'   => 'decimal:2',
        ];
    }

    /** Agente de parqueo al que pertenece esta liquidación. */
    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteParqueo::class, 'agente_parqueo_id');
    }
}
