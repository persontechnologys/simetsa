<?php

// app/Models/Comprobante.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Comprobante de pago (nota de venta interna) — Art. 19 Ordenanza SIMETSA.
 * Preparado para futura facturación electrónica SRI.
 *
 * @property int            $id
 * @property string         $concepto_type   App\Models\Ticket | App\Models\Infraccion
 * @property int            $concepto_id
 * @property string         $numero          CB-0001
 * @property float          $monto
 * @property \Carbon\Carbon $fecha_emision
 * @property string|null    $pdf_path
 */
class Comprobante extends Model
{
    /** @use HasFactory<\Database\Factories\ComprobanteFactory> */
    use HasFactory;

    protected $fillable = [
        'concepto_type',
        'concepto_id',
        'numero',
        'monto',
        'fecha_emision',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'monto'         => 'decimal:2',
            'fecha_emision' => 'datetime',
        ];
    }

    /**
     * Concepto cobrado: Ticket o Infraccion (ambos implementan Cobrable).
     */
    public function concepto(): MorphTo
    {
        return $this->morphTo();
    }
}
