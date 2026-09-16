<?php

// app/Http/Controllers/Api/InfraccionController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\InmovilizarRequest;
use App\Http\Requests\LiberarRequest;
use App\Http\Requests\PagarMultaRequest;
use App\Http\Requests\StoreInfraccionRequest;
use App\Http\Resources\InfraccionResource;
use App\Models\AgenteParqueo;
use App\Models\Conductor;
use App\Models\Infraccion;
use App\Enums\EstadoTransaccion;
use App\Services\ComprobanteService;
use App\Services\InfraccionService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoints de infracciones y candado inmovilizador para la app del agente en calle.
 *
 * Permisos requeridos (aplicados en routes/api.php):
 *  - GET    /conductor/infracciones       → infracciones.ver       (conductor)
 *  - POST   /infracciones                 → infracciones.registrar (agente)
 *  - GET    /infracciones/{id}            → infracciones.ver
 *  - POST   /infracciones/{id}/inmovilizar → inmovilizaciones.aplicar (agente)
 *  - POST   /infracciones/{id}/liberar    → inmovilizaciones.retirar
 *  - POST   /infracciones/{id}/pagar      → infracciones.ver        (conductor)
 *
 * Arts. 15, 17, 18, 28, 29, 30 — Ordenanza SIMETSA.
 */
class InfraccionController extends ApiController
{
    public function __construct(
        private readonly InfraccionService   $servicio,
        private readonly ComprobanteService  $comprobanteService,
    ) {
    }

    /**
     * Registra una nueva infracción desde la app del agente (Arts. 17, 18).
     *
     * @param  \App\Http\Requests\StoreInfraccionRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreInfraccionRequest $request): JsonResponse
    {
        $agente = AgenteParqueo::where('user_id', $request->user()->id)->first();

        if (! $agente) {
            return $this->error('El usuario autenticado no es un agente de parqueo.', null, 403);
        }

        try {
            $infraccion = $this->servicio->registrar($request->validated(), $agente);
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new InfraccionResource($infraccion->load(['agente', 'zona', 'calle'])),
            'Infracción registrada correctamente.',
            201,
        );
    }

    /**
     * Devuelve el detalle de una infracción (con inmovilización si existe).
     *
     * @param  \App\Models\Infraccion  $infraccion
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Infraccion $infraccion): JsonResponse
    {
        $this->authorize('view', $infraccion);

        return $this->exito(
            new InfraccionResource(
                $infraccion->load(['agente', 'zona', 'calle', 'conductor', 'inmovilizacion', 'comprobante'])
            ),
            'Detalle de la infracción.',
        );
    }

    /**
     * Coloca el candado inmovilizador sobre el vehículo (Art. 15).
     *
     * @param  \App\Http\Requests\InmovilizarRequest  $request
     * @param  \App\Models\Infraccion                 $infraccion
     * @return \Illuminate\Http\JsonResponse
     */
    public function inmovilizar(InmovilizarRequest $request, Infraccion $infraccion): JsonResponse
    {
        $agente = AgenteParqueo::where('user_id', $request->user()->id)->first();

        if (! $agente) {
            return $this->error('El usuario autenticado no es un agente de parqueo.', null, 403);
        }

        try {
            $inmovilizacion = $this->servicio->inmovilizar($infraccion, $agente, $request->validated());
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new InfraccionResource(
                $infraccion->fresh()->load(['agente', 'zona', 'inmovilizacion.agente'])
            ),
            'Vehículo inmovilizado correctamente.',
            201,
        );
    }

    /**
     * Libera el candado inmovilizador (Art. 15: queda sin efecto al pagar la infracción).
     * También permite liberación forzada por motivo administrativo.
     *
     * @param  \App\Http\Requests\LiberarRequest  $request
     * @param  \App\Models\Infraccion             $infraccion
     * @return \Illuminate\Http\JsonResponse
     */
    public function liberar(LiberarRequest $request, Infraccion $infraccion): JsonResponse
    {
        $infraccion->load('inmovilizacion');

        if (! $infraccion->inmovilizacion) {
            return $this->error('Esta infracción no tiene una inmovilización activa.', null, 422);
        }

        try {
            $this->servicio->liberar(
                $infraccion->inmovilizacion,
                $request->validated()['motivo'] ?? null,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new InfraccionResource(
                $infraccion->fresh()->load(['agente', 'zona', 'inmovilizacion'])
            ),
            'Candado retirado. Vehículo liberado.',
        );
    }

    // ── Conductor ─────────────────────────────────────────────────────────────

    /**
     * Historial paginado de infracciones del conductor autenticado.
     * Incluye infracciones por conductor_id o por placa de sus vehículos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function historialConductor(Request $request): JsonResponse
    {
        $conductor = Conductor::where('user_id', $request->user()->id)->first();

        if (! $conductor) {
            return $this->error('El usuario autenticado no es un conductor.', null, 403);
        }

        $historial = $this->servicio->historialConductor($conductor);

        return $this->exito(
            InfraccionResource::collection($historial),
            'Historial de infracciones.',
        );
    }

    /**
     * Inicia el pago de una multa por el conductor a través del gateway indicado.
     *
     * El gateway crea una TransaccionPago y devuelve la URL o QR de pago.
     * El webhook (`POST /pagos/webhook/{proveedor}`) confirma el pago y llama
     * a Infraccion::acreditar(), que marca la multa como pagada y libera
     * la inmovilización si existe (Art. 15).
     *
     * @param  \App\Http\Requests\PagarMultaRequest  $request
     * @param  \App\Models\Infraccion                $infraccion
     * @return \Illuminate\Http\JsonResponse
     */
    public function pagar(PagarMultaRequest $request, Infraccion $infraccion): JsonResponse
    {
        $conductor = Conductor::where('user_id', $request->user()->id)->first();

        if (! $conductor) {
            return $this->error('El usuario autenticado no es un conductor.', null, 403);
        }

        try {
            $transaccion = $this->servicio->iniciarPago(
                $infraccion,
                $conductor,
                $request->validated()['proveedor'],
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        // Proveedores sin gateway (none, manual) confirman el pago al instante.
        // Acreditar aquí, igual que lo hace el PagoWebhookController para gateways externos.
        $comprobanteId = null;
        if ($transaccion->estado === EstadoTransaccion::Completada) {
            $transaccion->concepto->acreditar($transaccion);
            try {
                $comprobante   = $this->comprobanteService->generar($transaccion->concepto, $transaccion);
                $comprobanteId = $comprobante->id;
            } catch (\Throwable $e) {
                Log::error('pagar: error al generar comprobante', [
                    'transaccion_id' => $transaccion->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        return $this->exito([
            'confirmado'        => $transaccion->estado === EstadoTransaccion::Completada,
            'transaccion_id'    => $transaccion->id,
            'estado'            => $transaccion->estado->value,
            'monto'             => $transaccion->monto,
            'moneda'            => $transaccion->moneda,
            'payment_url'       => $transaccion->payment_url,
            'qr_payload'        => $transaccion->qr_payload,
            'external_reference'=> $transaccion->external_reference,
            'comprobante_id'    => $comprobanteId,
        ], $transaccion->estado === EstadoTransaccion::Completada
            ? 'Pago confirmado.'
            : 'Pago iniciado. Completa el pago en el gateway.',
            201,
        );
    }
}
