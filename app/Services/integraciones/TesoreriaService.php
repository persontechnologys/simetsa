<?php
// app/Services/integraciones/TesoreriaService.php

namespace App\Services\integraciones;

use App\Models\Comprobante;
use Illuminate\Support\Facades\Log;

/**
 * Stub de integración con la Tesorería Municipal del GAD Salcedo.
 *
 * Permite reportar pagos acreditados al sistema de recaudación interno de la
 * Tesorería para la conciliación contable (Art. 19, Art. 21 — Ordenanza SIMETSA).
 * En Fase 10 este stub será reemplazado por la integración HTTP real o el
 * protocolo de conciliación que defina la Tesorería.
 *
 * La interfaz (firma de métodos) debe mantenerse estable para que
 * ComprobanteService no necesite cambios al implementar la integración real.
 *
 * STUB — reemplazar con HTTP real en Fase 10.
 */
class TesoreriaService
{
    /**
     * Reporta un comprobante de pago acreditado a la Tesorería Municipal
     * para la conciliación contable (Art. 19, Art. 21).
     *
     * STUB: no realiza llamadas HTTP. Registra el intento en el log y retorna
     * siempre true para que el flujo continúe normalmente.
     *
     * @param  \App\Models\Comprobante  $comprobante  Comprobante emitido tras el pago.
     * @return bool  true si la Tesorería aceptó el reporte (siempre true en stub).
     */
    public function reportarPago(Comprobante $comprobante): bool
    {
        Log::info('Tesorería stub — reporte de pago', [
            'comprobante_id' => $comprobante->id,
            'numero'         => $comprobante->numero,
            'monto'          => $comprobante->monto,
            'concepto_type'  => $comprobante->concepto_type,
            'concepto_id'    => $comprobante->concepto_id,
            'fecha_emision'  => $comprobante->fecha_emision?->toIso8601String(),
        ]);

        // STUB: retorna siempre true para no interrumpir el flujo de acreditación.
        return true;
    }
}
