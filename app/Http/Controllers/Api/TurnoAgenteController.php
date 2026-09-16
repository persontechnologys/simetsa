<?php
// app/Http/Controllers/Api/TurnoAgenteController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreTurnoAgenteRequest;
use App\Http\Requests\UpdateTurnoAgenteRequest;
use App\Http\Resources\TurnoAgenteResource;
use App\Models\AgenteParqueo;
use App\Models\TurnoAgente;
use App\Services\FiscalizacionService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de gestión de turnos del agente de parqueo.
 *
 * Permisos (aplicados en routes/api.php):
 *  - POST   /api/v1/turnos                   → turnos.iniciar
 *  - PATCH  /api/v1/turnos/{turno}/finalizar  → turnos.iniciar
 *  - GET    /api/v1/turnos/activo             → turnos.ver
 *
 * Art. 38 — Ordenanza SIMETSA.
 */
class TurnoAgenteController extends ApiController
{
    public function __construct(private readonly FiscalizacionService $servicio) {}

    /**
     * Inicia un nuevo turno para el agente autenticado.
     *
     * @param  \App\Http\Requests\StoreTurnoAgenteRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTurnoAgenteRequest $request): JsonResponse
    {
        $agente = AgenteParqueo::where('user_id', $request->user()->id)->first();

        if (! $agente) {
            return $this->error('El usuario autenticado no es un agente de parqueo.', null, 403);
        }

        try {
            $turno = $this->servicio->iniciarTurno($agente, $request->validated());
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new TurnoAgenteResource($turno->load('agente')),
            'Turno iniciado correctamente.',
            201,
        );
    }

    /**
     * Finaliza el turno activo indicado.
     *
     * @param  \App\Http\Requests\UpdateTurnoAgenteRequest  $request
     * @param  \App\Models\TurnoAgente                      $turno
     * @return \Illuminate\Http\JsonResponse
     */
    public function finalizar(UpdateTurnoAgenteRequest $request, TurnoAgente $turno): JsonResponse
    {
        $agente = AgenteParqueo::where('user_id', $request->user()->id)->first();

        if (! $agente || $turno->agente_parqueo_id !== $agente->id) {
            return $this->error('No tenés permiso para finalizar este turno.', null, 403);
        }

        try {
            $turno = $this->servicio->finalizarTurno($turno, $request->validated());
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new TurnoAgenteResource($turno->load('agente')),
            'Turno finalizado correctamente.',
        );
    }

    /**
     * Devuelve el turno activo del agente autenticado (null si no hay ninguno).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activo(Request $request): JsonResponse
    {
        $agente = AgenteParqueo::where('user_id', $request->user()->id)->first();

        if (! $agente) {
            return $this->error('El usuario autenticado no es un agente de parqueo.', null, 403);
        }

        $turno = $this->servicio->turnoActivo($agente);

        return $this->exito(
            $turno ? new TurnoAgenteResource($turno->load('agente')) : null,
            $turno ? 'Turno activo encontrado.' : 'Sin turno activo.',
        );
    }
}
