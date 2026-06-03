<?php
// app/Http/Resources/AgenteParqueoResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación JSON del agente de parqueo para la app móvil (Fase 9).
 *
 * Relaciones esperadas cargadas: 'user', 'asignaciones.zona'.
 * La zona se incluye solo cuando la relación 'asignaciones' está cargada
 * (usando whenLoaded para no romper llamadas que no la precargan).
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
            'zona_actual'        => $this->whenLoaded('asignaciones', function () {
                $asignacion = $this->asignaciones->firstWhere('activa', true);
                $zona = $asignacion?->zona;
                if (! $zona) return null;

                return [
                    'id'         => $zona->id,
                    'nombre'     => $zona->nombre,
                    'codigo'     => $zona->codigo,
                    'color'      => $zona->color,
                    'centro_lat' => $zona->centro_lat,
                    'centro_lng' => $zona->centro_lng,
                    'zoom'       => $zona->zoom ?? 16,
                    // Polígono como array de [lat, lng] — app lo convierte a { latitude, longitude }
                    'poligono'   => $zona->poligono,
                ];
            }),
        ];
    }
}
