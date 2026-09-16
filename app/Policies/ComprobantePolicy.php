<?php

// app/Policies/ComprobantePolicy.php

namespace App\Policies;

use App\Models\Comprobante;
use App\Models\User;

/**
 * Política de autorización para Comprobantes (Art. 19 — Ordenanza SIMETSA).
 *
 * El conductor solo puede ver sus propios comprobantes.
 * El backoffice (comisario, director, admin) puede ver todos.
 */
class ComprobantePolicy
{
    /** Ver listado de comprobantes — solo backoffice. */
    public function viewAny(User $user): bool
    {
        return $user->can('comprobantes.ver') && ! $user->hasRole('conductor');
    }

    /**
     * Ver un comprobante específico.
     *
     * Conductor: solo el de sus tickets o infracciones.
     * Backoffice: todos.
     */
    public function view(User $user, Comprobante $comprobante): bool
    {
        if (! $user->can('comprobantes.ver')) {
            return false;
        }

        if ($user->hasRole('conductor')) {
            $concepto  = $comprobante->concepto;
            $conductor = $concepto?->conductor ?? null;
            return $conductor?->user_id === $user->id;
        }

        return true;
    }
}
