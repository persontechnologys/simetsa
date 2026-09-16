<?php
// app/Http/Controllers/Api/PagoEfectivoController.php

namespace App\Http\Controllers\Api;

use App\Services\PagoEfectivoService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints del flujo de pago en efectivo con confirmación OTP (Art. 14, 19).
 *
 * Rutas:
 *   POST /api/v1/movil/pagos/efectivo/solicitar  → conductor solicita código OTP
 *   POST /api/v1/movil/pagos/efectivo/confirmar  → agente confirma el cobro
 */
class PagoEfectivoController extends ApiController
{
    public function __construct(private readonly PagoEfectivoService $servicio)
    {
    }

    /**
     * Conductor solicita un código OTP para pagar un ticket o infracción en efectivo.
     *
     * @see Art. 14 Ordenanza SIMETSA — pago en efectivo al agente.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function solicitar(Request $request): JsonResponse
    {
        $request->validate([
            'tipo'       => ['required', 'string', 'in:ticket,infraccion'],
            'concepto_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $resultado = $this->servicio->solicitarOtp(
                $request->input('tipo'),
                (int) $request->input('concepto_id'),
                $request->user(),
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito($resultado, 'Código generado. Mostralo al agente y entregá el efectivo.');
    }

    /**
     * Agente ingresa el código OTP para confirmar la recepción del efectivo.
     *
     * @see Art. 14, 19 Ordenanza SIMETSA.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmar(Request $request): JsonResponse
    {
        $request->validate([
            'otp_codigo' => ['required', 'string', 'size:6'],
        ]);

        try {
            $resultado = $this->servicio->confirmarOtp(
                $request->input('otp_codigo'),
                $request->user(),
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito([
            'comprobante_id'    => $resultado['comprobante_id'],
            'numero_comprobante' => $resultado['numero_comprobante'],
            'monto'             => (float) $resultado['transaccion']->monto,
        ], 'Pago confirmado. Se generó el comprobante.');
    }
}
