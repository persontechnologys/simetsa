<?php

// app/Http/Resources/TicketResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación JSON del ticket digital para la app móvil.
 *
 * @mixin \App\Models\Ticket
 */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'codigo'           => $this->codigo,
            'estado'           => $this->estado->value,
            'estado_label'     => $this->estado->etiqueta(),
            'estado_color'     => $this->estado->color(),
            'horas_compradas'  => $this->horas_compradas,
            'monto'            => (float) $this->monto,
            'metodo_pago'      => $this->metodo_pago->value,
            'metodo_pago_label'=> $this->metodo_pago->etiqueta(),
            'proveedor'        => $this->proveedor->value,
            'es_exonerado'     => $this->es_exonerado,
            'tipo_exoneracion' => $this->tipo_exoneracion,
            'comprado_en'      => $this->comprado_en?->toIso8601String(),
            'expira_en'        => $this->expira_en?->toIso8601String(),

            // Relaciones (cuando estén cargadas)
            'vehiculo' => $this->whenLoaded('vehiculo', fn () => [
                'id'    => $this->vehiculo->id,
                'placa' => $this->vehiculo->placa,
                'marca' => $this->vehiculo->marca,
                'color' => $this->vehiculo->color,
            ]),
            'zona' => $this->whenLoaded('zona', fn () => [
                'id'     => $this->zona->id,
                'codigo' => $this->zona->codigo,
                'nombre' => $this->zona->nombre,
            ]),
            'calle' => $this->whenLoaded('calle', fn () => $this->calle ? [
                'id'     => $this->calle->id,
                'nombre' => $this->calle->nombre,
            ] : null),
            'sesion' => $this->whenLoaded('sesion', fn () => $this->sesion
                ? new SesionParqueoResource($this->sesion)
                : null),
            'comprobante_id' => $this->whenLoaded(
                'comprobante',
                fn () => $this->comprobante?->id,
            ),
            'payment_url' => $this->whenLoaded(
                'transacciones',
                fn () => $this->transacciones->last()?->payment_url,
            ),
        ];
    }
}
