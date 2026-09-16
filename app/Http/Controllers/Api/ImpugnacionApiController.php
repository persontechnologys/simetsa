<?php

// app/Http/Controllers/Api/ImpugnacionApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreImpugnacionRequest;
use App\Http\Resources\ImpugnacionResource;
use App\Models\Conductor;
use App\Models\Infraccion;
use App\Services\ImpugnacionService;
use DomainException;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints de impugnaciones para la app móvil (conductor).
 *
 * Permisos (aplicados en routes/api.php):
 *  - POST /api/v1/infracciones/{infraccion}/impugnacion → impugnaciones.registrar
 *  - GET  /api/v1/infracciones/{infraccion}/impugnacion → impugnaciones.ver
 *
 * Art. 17.f — Ordenanza SIMETSA.
 */
class ImpugnacionApiController extends ApiController
{
    public function __construct(private readonly ImpugnacionService $servicio)
    {
    }

    /**
     * Presenta una impugnación para la infracción indicada (Art. 17.f).
     *
     * POST /api/v1/infracciones/{infraccion}/impugnacion
     */
    public function store(StoreImpugnacionRequest $request, Infraccion $infraccion): JsonResponse
    {
        $conductor = Conductor::where('user_id', $request->user()->id)->first();

        if (! $conductor) {
            return $this->error('No se encontró un perfil de conductor para el usuario autenticado.', null, 403);
        }

        // Verificar ownership: la infracción debe corresponder al conductor (por id o placa)
        $esDelConductor = $infraccion->conductor_id === $conductor->id
            || $conductor->vehiculos()->where('placa', $infraccion->placa)->exists();

        if (! $esDelConductor) {
            return $this->error('No puede impugnar una infracción que no corresponde a sus vehículos.', null, 403);
        }

        try {
            $impugnacion = $this->servicio->presentar(
                $infraccion,
                $conductor,
                $request->validated()['motivo'],
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new ImpugnacionResource($impugnacion),
            'Impugnación presentada correctamente.',
            201,
        );
    }

    /**
     * Retorna el estado de la impugnación de la infracción indicada.
     *
     * GET /api/v1/infracciones/{infraccion}/impugnacion
     */
    public function show(Infraccion $infraccion): JsonResponse
    {
        $impugnacion = $infraccion->impugnacion;

        if (! $impugnacion) {
            return $this->error('Esta infracción no tiene impugnación registrada.', null, 404);
        }

        return $this->exito(
            new ImpugnacionResource($impugnacion),
            'Impugnación encontrada.',
        );
    }
}
