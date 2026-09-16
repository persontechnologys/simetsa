<?php

// app/Enums/EstadoOrdenPago.php

namespace App\Enums;

/**
 * Estados posibles de una Orden de Pago (Art. 28 — Ordenanza SIMETSA).
 */
enum EstadoOrdenPago: string
{
    case Pendiente = 'pendiente';
    case Pagada    = 'pagada';
    case Anulada   = 'anulada';
    case Vencida   = 'vencida';

    public function etiqueta(): string
    {
        return match($this) {
            self::Pendiente => 'Pendiente',
            self::Pagada    => 'Pagada',
            self::Anulada   => 'Anulada',
            self::Vencida   => 'Vencida',
        };
    }

    public function claseBadge(): string
    {
        return match($this) {
            self::Pendiente => 'warning',
            self::Pagada    => 'success',
            self::Anulada   => 'secondary',
            self::Vencida   => 'danger',
        };
    }
}
