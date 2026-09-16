<?php
// app/Http/Controllers/Api/IncidenteCalleController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreIncidenteCalleRequest;
use App\Http\Resources\IncidenteCalleResource;
use App\Models\AgenteParqueo;
use App\Services\FiscalizacionService;
use DomainException;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint para registrar incidentes de calle con notificación al ECU 911.
 *
 * Permisos (aplicados en routes/api.php):
 *  - POST /api/v1/incidentes → incidentes.registrar
 *
 * Art. 38.m — el agente debe comunicar al ECU 911 ante incidentes.
 */
class IncidenteCalleController extends ApiController
{
    public function __construct(private readonly FiscalizacionService $servicio) {}

    /**
     * Registra un incidente de calle y notifica al ECU 911 (stub).
     *
     * @param  \App\Http\Requests\StoreIncidenteCalleRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreIncidenteCalleRequest $request): JsonResponse
    {
        $agente = AgenteParqueo::where('user_id', $request->user()->id)->first();

        if (! $agente) {
            return $this->error('El usuario autenticado no es un agente de parqueo.', null, 403);
        }

        $turno = $this->servicio->turnoActivo($agente);

        if (! $turno) {
            return $this->error('No tenés un turno activo. Iniciá un turno antes de reportar un incidente.', null, 422);
        }

        try {
            $incidente = $this->servicio->registrarIncidente($turno, $request->validated());
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new IncidenteCalleResource($incidente->load('turno')),
            'Incidente registrado y notificado al ECU 911.',
            201,
        );
    }
}
