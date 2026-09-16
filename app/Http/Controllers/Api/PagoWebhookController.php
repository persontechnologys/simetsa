<?php

// app/Http/Controllers/Api/PagoWebhookController.php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoTransaccion;
use App\Services\ComprobanteService;
use App\Services\Pagos\PagoManager;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint receptor de callbacks/webhooks de los proveedores de pago (Art. 21).
 *
 * Ruta: POST /api/v1/pagos/webhook/{proveedor}
 *
 * Este endpoint es público (sin auth Sanctum) — los gateways no envían
 * tokens de usuario. La autenticidad del callback se verifica mediante
 * la firma HMAC en cada proveedor (en modo fake, la firma se ignora).
 *
 * Idempotencia: si la transacción ya está en estado terminal,
 * devuelve 200 sin modificar nada.
 */
class PagoWebhookController extends ApiController
{
    public function __construct(
        private readonly PagoManager $pagoManager,
        private readonly ComprobanteService $comprobanteService,
    ) {
    }

    /**
     * Procesa el callback de un gateway de pago.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $proveedor  Nombre del proveedor (ej. 'deuna').
     * @return \Illuminate\Http\JsonResponse
     */
    public function recibir(Request $request, string $proveedor): JsonResponse
    {
        try {
            $provider    = $this->pagoManager->proveedor($proveedor);
            $firma       = $request->header('X-Signature', '');
            $transaccion = $provider->procesarWebhook($request->all(), $firma);
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        // Si el pago se completó, acreditar al concepto cobrado (Ticket, etc.)
        if ($transaccion->estado === EstadoTransaccion::Completada) {
            $transaccion->concepto->acreditar($transaccion);

            // Generar comprobante de pago automáticamente (Art. 19).
            try {
                $this->comprobanteService->generar($transaccion->concepto, $transaccion);
            } catch (\Throwable $e) {
                // No interrumpir el webhook si el comprobante falla.
                Log::error('Webhook: error al generar comprobante', [
                    'transaccion_id' => $transaccion->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        return $this->exito(['recibido' => true], 'Webhook procesado.');
    }
}
