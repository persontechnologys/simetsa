<?php

// app/Http/Resources/ImpugnacionResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialización de Impugnacion para la API móvil.
 */
class ImpugnacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'infraccion_id'=> $this->infraccion_id,
            'conductor_id' => $this->conductor_id,
            'motivo'       => $this->motivo,
            'estado'       => $this->estado,
            'resolucion'   => $this->resolucion,
            'resuelto_por' => $this->resuelto_por,
            'resuelto_at'  => $this->resuelto_at?->toISOString(),
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
