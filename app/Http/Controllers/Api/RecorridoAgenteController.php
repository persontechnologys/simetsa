<?php
// app/Http/Controllers/Api/RecorridoAgenteController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreRecorridoAgenteRequest;
use App\Http\Resources\RecorridoAgenteResource;
use App\Models\AgenteParqueo;
use App\Services\FiscalizacionService;
use DomainException;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint para registrar posiciones GPS del recorrido del agente.
 *
 * Permisos (aplicados en routes/api.php):
 *  - POST /api/v1/recorridos → turnos.iniciar
 *
 * Art. 38 — Ordenanza SIMETSA.
 */
class RecorridoAgenteController extends ApiController
{
    public function __construct(private readonly FiscalizacionService $servicio) {}

    /**
     * Registra la posición GPS actual del agente en su turno activo.
     *
     * @param  \App\Http\Requests\StoreRecorridoAgenteRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRecorridoAgenteRequest $request): JsonResponse
    {
        $agente = AgenteParqueo::where('user_id', $request->user()->id)->first();

        if (! $agente) {
            return $this->error('El usuario autenticado no es un agente de parqueo.', null, 403);
        }

        $turno = $this->servicio->turnoActivo($agente);

        if (! $turno) {
            return $this->error('No tenés un turno activo. Iniciá un turno antes de registrar posición.', null, 422);
        }

        try {
            $recorrido = $this->servicio->registrarPosicion($turno, $request->validated());
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new RecorridoAgenteResource($recorrido),
            'Posición registrada.',
            201,
        );
    }
}
