<?php

// app/Http/Resources/DispositivoMovilResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación JSON del dispositivo móvil para la app móvil.
 *
 * @mixin \App\Models\DispositivoMovil
 */
class DispositivoMovilResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'plataforma'         => $this->plataforma,
            'canal'              => $this->canal,
            'tipo_app'           => $this->tipo_app,
            'modelo_dispositivo' => $this->modelo_dispositivo,
            'activo'             => $this->activo,
            'ultimo_uso_at'      => $this->ultimo_uso_at?->toIso8601String(),
            'created_at'         => $this->created_at?->toIso8601String(),
        ];
    }
}
