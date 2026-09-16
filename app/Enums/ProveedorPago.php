<?php

/**
 * app/Enums/ProveedorPago.php
 *
 * Proveedores de gateway de pago disponibles en el SIMETSA.
 * Separa el "medio" (MetodoPago) de la "pasarela" que lo procesa.
 */

namespace App\Enums;

enum ProveedorPago: string
{
    /** Sin gateway — pago en efectivo directo (inmediato, sin confirmación). */
    case None       = 'none';

    /** Proveedor manual / simulado — para desarrollo y tests. */
    case Manual     = 'manual';

    /** Deuna — pasarela digital principal (Art. 21). */
    case Deuna      = 'deuna';

    /** Pagomedios — reservado para futura integración. */
    case Pagomedios = 'pagomedios';

    /** Efectivo con confirmación OTP — agente verifica el cobro (Art. 14). */
    case Efectivo   = 'efectivo';

    /** Etiqueta legible para humanos. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::None       => 'Ninguno',
            self::Manual     => 'Manual',
            self::Deuna      => 'Deuna',
            self::Pagomedios => 'Pagomedios',
            self::Efectivo   => 'Efectivo (OTP)',
        };
    }

    /** True si el proveedor es un gateway digital externo. */
    public function esDigital(): bool
    {
        return in_array($this, [self::Deuna, self::Pagomedios], true);
    }
}
