<?php
// app/Http/Resources/CredencialDiscapacidadResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Representación JSON de una credencial CONADIS para la app móvil (Art. 26 Ordenanza SIMETSA).
 */
class CredencialDiscapacidadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'conductor_id'            => $this->conductor_id,
            'numero_conadis'          => $this->numero_conadis,
            'nombre_beneficiario'     => $this->nombre_beneficiario,
            'porcentaje_discapacidad' => $this->porcentaje_discapacidad,
            'fecha_emision'           => $this->fecha_emision?->toDateString(),
            'fecha_vencimiento'       => $this->fecha_vencimiento?->toDateString(),
            'estado'                  => $this->estado,
            'url_archivo'             => $this->ruta_archivo ? Storage::url($this->ruta_archivo) : null,
            'observaciones'           => $this->observaciones,
            'aprobada_por'            => $this->aprobada_por,
            'fecha_aprobacion'        => $this->fecha_aprobacion?->toDateTimeString(),
            'fecha_solicitud'         => $this->created_at?->toDateString(),
        ];
    }
}
