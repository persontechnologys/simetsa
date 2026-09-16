<?php

// app/Http/Controllers/Api/OrdenPagoApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreOrdenPagoRequest;
use App\Http\Resources\OrdenPagoResource;
use App\Models\Infraccion;
use App\Services\OrdenPagoService;
use DomainException;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints de Órdenes de Pago para la API (Art. 28 — Ordenanza SIMETSA).
 *
 * POST /api/v1/ordenes-pago — generar orden de pago para una infracción
 */
class OrdenPagoApiController extends ApiController
{
    public function __construct(private readonly OrdenPagoService $ordenPagoService)
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:ordenes_pago.generar', ['only' => ['store']]);
    }

    /**
     * Genera una nueva Orden de Pago para una infracción pendiente.
     *
     * Art. 28 — el comisario emite la orden antes de que el conductor pueda pagar.
     */
    public function store(StoreOrdenPagoRequest $request): JsonResponse
    {
        $infraccion = Infraccion::findOrFail($request->validated('infraccion_id'));

        try {
            $ordenPago = $this->ordenPagoService->generar($infraccion, $request->user());
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        $ordenPago->load('infraccion', 'generadaPor');

        return $this->exito(new OrdenPagoResource($ordenPago), 'Orden de pago generada.', 201);
    }
}
