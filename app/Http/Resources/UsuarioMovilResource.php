<?php
// app/Http/Resources/UsuarioMovilResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación JSON del usuario autenticado en la app móvil.
 *
 * Incluye roles y permisos de Spatie para que la app pueda controlar
 * visibilidad de pantallas y acciones sin consultar el backend en cada navegación.
 *
 * Requiere que el modelo User tenga cargados roles y permissions
 * (vía load('roles', 'permissions') o con('roles', 'permissions')).
 *
 * @mixin \App\Models\User
 */
class UsuarioMovilResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'nombre'   => $this->name,
            'email'    => $this->email,
            // getRoleNames() es un método de Spatie HasRoles — devuelve Collection de strings
            'roles'    => $this->getRoleNames()->values(),
            // getAllPermissions() devuelve todos los permisos (directos + heredados de roles)
            'permisos' => $this->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
