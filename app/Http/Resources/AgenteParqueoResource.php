<?php
// app/Http/Resources/AgenteParqueoResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación JSON del agente de parqueo para la app móvil (Fase 9).
 *
 * Espera que la relación 'user' esté cargada.
 */
class AgenteParqueoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'codigo'             => $this->codigo,
            'estado'             => $this->estado,
            'nombre'             => $this->nombre_completo,
            'email'              => $this->user?->email,
            'numero_credencial'  => $this->numero_credencial,
            'fecha_autorizacion' => $this->fecha_autorizacion?->toDateString(),
        ];
    }
}
