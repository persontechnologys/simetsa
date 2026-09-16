<?php
// app/Policies/CredencialDiscapacidadPolicy.php

namespace App\Policies;

use App\Models\Conductor;
use App\Models\CredencialDiscapacidad;
use App\Models\User;

/**
 * Policy de credenciales CONADIS (Art. 26 Ordenanza SIMETSA).
 *
 * El conductor solo accede a las credenciales de sus propios vehículos.
 * Comisario y super_admin tienen visibilidad total (bypass en before()).
 */
class CredencialDiscapacidadPolicy
{
    /**
     * Bypass para super_admin y comisario (visibilidad y gestión total).
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('comisario')) {
            return true;
        }

        return null;
    }

    /**
     * @see Art. 26 Ordenanza SIMETSA.
     */
    public function create(User $user): bool
    {
        return $user->can('credenciales_discapacidad.crear');
    }

    /**
     * El conductor solo puede ver su propia credencial.
     */
    public function view(User $user, CredencialDiscapacidad $credencial): bool
    {
        if (! $user->can('credenciales_discapacidad.ver')) {
            return false;
        }

        if ($user->hasRole('conductor')) {
            $conductor = Conductor::where('user_id', $user->id)->first();

            return $conductor && $credencial->conductor_id === $conductor->id;
        }

        return true;
    }

    /**
     * Aprobar o rechazar credencial — solo roles con permiso explícito.
     *
     * @see Art. 26 Ordenanza SIMETSA.
     */
    public function aprobar(User $user, CredencialDiscapacidad $credencial): bool
    {
        return $user->can('credenciales_discapacidad.aprobar');
    }

    /**
     * No se permite edición directa (el flujo es solicitar → aprobar/rechazar).
     */
    public function update(User $user, CredencialDiscapacidad $credencial): bool
    {
        return false;
    }

    /**
     * No se permite eliminación (se usa soft delete solo en revisión interna).
     */
    public function delete(User $user, CredencialDiscapacidad $credencial): bool
    {
        return false;
    }
}
