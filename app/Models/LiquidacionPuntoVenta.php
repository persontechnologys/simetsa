<?php

// app/Models/LiquidacionPuntoVenta.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Liquidación mensual de un Punto de Venta (Art. 21 — 90 % de lo recaudado).
 *
 * Nota de deuda técnica: monto_bruto se calcula en 0 hasta que la tabla
 * tickets incorpore punto_venta_id (ver deuda técnica en CLAUDE.md).
 *
 * @property int            $id
 * @property int            $punto_venta_id
 * @property \Carbon\Carbon $periodo_mes   Primer día del mes (ej. 2026-06-01).
 * @property float          $monto_bruto
 * @property float          $porcentaje    90 por defecto.
 * @property float          $monto_neto
 */
class LiquidacionPuntoVenta extends Model
{
    /** @use HasFactory<\Database\Factories\LiquidacionPuntoVentaFactory> */
    use HasFactory;

    /** @var string Nombre explícito (Laravel inferiría 'liquidacion_punto_ventas'). */
    protected $table = 'liquidaciones_punto_venta';

    protected $fillable = [
        'punto_venta_id',
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

    /** Punto de venta al que pertenece esta liquidación. */
    public function puntoVenta(): BelongsTo
    {
        return $this->belongsTo(PuntoVenta::class);
    }
}
