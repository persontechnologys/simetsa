<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComprobanteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'numero'        => $this->numero,
            'monto'         => $this->monto,
            'fecha_emision' => $this->fecha_emision?->toIso8601String(),
            'pdf_url'       => route('api.comprobantes.pdf', $this->id),
            'concepto'      => $this->whenLoaded('concepto', fn () => [
                'tipo'       => class_basename($this->concepto_type),
                'id'         => $this->concepto_id,
                'descripcion'=> $this->concepto?->descripcionCobro(),
            ]),
            'creado_en'     => $this->created_at?->toIso8601String(),
        ];
    }
}
