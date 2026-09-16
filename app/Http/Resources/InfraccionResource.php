<?php

// app/Http/Resources/InfraccionResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación JSON de una infracción SIMETSA para la app móvil y el backoffice.
 *
 * @mixin \App\Models\Infraccion
 */
class InfraccionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'placa'             => $this->placa,
            'tipo_infraccion'   => $this->tipo_infraccion->value,
            'tipo_label'        => $this->tipo_infraccion->etiqueta(),
            'estado'            => $this->estado->value,
            'estado_label'      => $this->estado->etiqueta(),
            'estado_color'      => $this->estado->color(),
            'monto_multa'       => $this->monto_multa,
            'sbu_vigente'       => $this->sbu_vigente,
            'minutos_excedidos' => $this->minutos_excedidos,
            'descripcion'       => $this->descripcion,
            'foto_evidencia'    => $this->foto_evidencia
                ? asset('storage/' . $this->foto_evidencia)
                : null,
            'latitud'  => $this->latitud,
            'longitud' => $this->longitud,
            'registrada_en' => $this->created_at?->toIso8601String(),

            'zona' => $this->whenLoaded('zona', fn () => [
                'id'     => $this->zona->id,
                'nombre' => $this->zona->nombre,
                'codigo' => $this->zona->codigo,
            ]),
            'calle' => $this->whenLoaded('calle', fn () => $this->calle ? [
                'id'     => $this->calle->id,
                'nombre' => $this->calle->nombre,
            ] : null),
            'agente' => $this->whenLoaded('agente', fn () => [
                'id'     => $this->agente->id,
                'codigo' => $this->agente->codigo,
            ]),
            'conductor' => $this->whenLoaded('conductor', fn () => $this->conductor ? [
                'id'     => $this->conductor->id,
            ] : null),
            'inmovilizacion' => $this->whenLoaded(
                'inmovilizacion',
                fn () => $this->inmovilizacion
                    ? new InmovilizacionResource($this->inmovilizacion)
                    : null,
            ),
            'comprobante_id' => $this->whenLoaded(
                'comprobante',
                fn () => $this->comprobante?->id,
            ),
        ];
    }
}
