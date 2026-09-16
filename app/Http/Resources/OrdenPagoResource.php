<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenPagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'numero_orden'     => $this->numero_orden,
            'monto'            => $this->monto,
            'estado'           => $this->estado->value,
            'estado_etiqueta'  => $this->estado->etiqueta(),
            'vence_at'         => $this->vence_at?->toIso8601String(),
            'motivo_anulacion' => $this->motivo_anulacion,
            'generada_por'     => $this->whenLoaded('generadaPor', fn () => $this->generadaPor->name),
            'infraccion'       => $this->whenLoaded('infraccion', fn () => [
                'id'              => $this->infraccion->id,
                'placa'           => $this->infraccion->placa,
                'tipo_infraccion' => $this->infraccion->tipo_infraccion->value,
                'monto_multa'     => $this->infraccion->monto_multa,
            ]),
            'creado_en'        => $this->created_at?->toIso8601String(),
        ];
    }
}
