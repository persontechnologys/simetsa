<?php
// app/Http/Resources/RecorridoAgenteResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialización de un punto GPS del recorrido del agente.
 */
class RecorridoAgenteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'turno_agente_id'=> $this->turno_agente_id,
            'latitud'        => $this->latitud,
            'longitud'       => $this->longitud,
            'registrado_at'  => $this->registrado_at?->toISOString(),
        ];
    }
}
