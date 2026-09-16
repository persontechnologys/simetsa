<?php
// app/Http/Resources/IncidenteCalleResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialización de un incidente de calle para la API móvil.
 */
class IncidenteCalleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'turno_agente_id'  => $this->turno_agente_id,
            'tipo'             => $this->tipo?->value,
            'tipo_etiqueta'    => $this->tipo?->etiqueta(),
            'descripcion'      => $this->descripcion,
            'latitud'          => $this->latitud,
            'longitud'         => $this->longitud,
            'foto_evidencia_url' => $this->foto_evidencia
                ? asset('storage/' . $this->foto_evidencia)
                : null,
            'reportado_ecu911' => $this->reportado_ecu911,
            'notificado_at'    => $this->notificado_at?->toISOString(),
            'creado_en'        => $this->created_at?->toISOString(),
        ];
    }
}
