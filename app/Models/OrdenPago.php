<?php

// app/Models/OrdenPago.php

namespace App\Models;

use App\Enums\EstadoOrdenPago;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Orden de Pago formal de una multa (Art. 28 — Ordenanza SIMETSA).
 *
 * @property int                $id
 * @property int                $infraccion_id
 * @property string             $numero_orden
 * @property float              $monto
 * @property EstadoOrdenPago    $estado
 * @property \Carbon\Carbon|null $vence_at
 * @property int                $generada_por
 * @property string|null        $motivo_anulacion
 */
class OrdenPago extends Model
{
    /** @use HasFactory<\Database\Factories\OrdenPagoFactory> */
    use HasFactory;

    /** @var string Nombre de tabla explícito (Laravel inferiría 'orden_pagos'). */
    protected $table = 'ordenes_pago';

    protected $fillable = [
        'infraccion_id',
        'numero_orden',
        'monto',
        'estado',
        'vence_at',
        'generada_por',
        'motivo_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'estado'   => EstadoOrdenPago::class,
            'monto'    => 'decimal:2',
            'vence_at' => 'datetime',
        ];
    }

    /** Infracción a la que corresponde esta orden de pago (Art. 28). */
    public function infraccion(): BelongsTo
    {
        return $this->belongsTo(Infraccion::class);
    }

    /** Usuario que generó la orden (comisario, Art. 37). */
    public function generadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generada_por');
    }
}
