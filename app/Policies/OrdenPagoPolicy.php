<?php

// app/Policies/OrdenPagoPolicy.php

namespace App\Policies;

use App\Models\OrdenPago;
use App\Models\User;

/**
 * Política de autorización para Órdenes de Pago (Art. 28 — Ordenanza SIMETSA).
 * La autorización granular se delega a los permisos de Spatie (ordenes_pago.*).
 */
class OrdenPagoPolicy
{
    /** Ver listado de órdenes de pago. */
    public function viewAny(User $user): bool
    {
        return $user->can('ordenes_pago.ver');
    }

    /** Ver una orden de pago específica. */
    public function view(User $user, OrdenPago $ordenPago): bool
    {
        return $user->can('ordenes_pago.ver');
    }

    /** Generar una nueva orden de pago. */
    public function create(User $user): bool
    {
        return $user->can('ordenes_pago.generar');
    }
}
