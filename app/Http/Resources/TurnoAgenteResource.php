<?php
// app/Http/Resources/TurnoAgenteResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialización del turno de agente para la API móvil.
 */
class TurnoAgenteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'agente_parqueo_id' => $this->agente_parqueo_id,
            'agente'          => $this->when($this->relationLoaded('agente'), fn () => [
                'id'     => $this->agente?->id,
                'codigo' => $this->agente?->codigo,
            ]),
            'inicio_at'       => $this->inicio_at?->toISOString(),
            'fin_at'          => $this->fin_at?->toISOString(),
            'estado'          => $this->estado?->value,
            'estado_etiqueta' => $this->estado?->etiqueta(),
            'observaciones'   => $this->observaciones,
            'duracion_minutos'=> $this->duracionMinutos(),
            'minutos_transcurridos' => $this->estaActivo() ? $this->minutosTranscurridos() : null,
            'creado_en'       => $this->created_at?->toISOString(),
        ];
    }
}
