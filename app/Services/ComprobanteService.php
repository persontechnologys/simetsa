<?php

// app/Services/ComprobanteService.php

namespace App\Services;

use App\Contracts\Cobrable;
use App\Models\Comprobante;
use App\Models\TransaccionPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Generación y obtención de Comprobantes de pago (nota de venta interna).
 *
 * Art. 19 — todo pago confirmado debe emitir un comprobante.
 * El número es interno (no SRI); preparado para facturación electrónica futura.
 */
class ComprobanteService
{
    /**
     * Genera un comprobante para el concepto cobrado.
     *
     * Idempotente: si ya existe un comprobante para el mismo concepto,
     * devuelve el existente sin crear uno nuevo.
     *
     * @param  Model&Cobrable    $concepto    Ticket o Infraccion.
     * @param  TransaccionPago   $transaccion Transacción completada.
     * @return Comprobante
     */
    public function generar(Model&Cobrable $concepto, TransaccionPago $_transaccion): Comprobante
    {
        $existente = Comprobante::where('concepto_type', get_class($concepto))
            ->where('concepto_id', $concepto->id)
            ->first();

        if ($existente) {
            return $existente;
        }

        try {
            return Comprobante::create([
                'concepto_type' => get_class($concepto),
                'concepto_id'   => $concepto->id,
                'numero'        => $this->generarNumero(),
                'monto'         => $concepto->montoCobrable(),
                'fecha_emision' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ComprobanteService: error al generar comprobante', [
                'concepto_type' => get_class($concepto),
                'concepto_id'   => $concepto->id,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Genera el número correlativo del comprobante (CB-0001).
     */
    private function generarNumero(): string
    {
        $siguiente = (Comprobante::max('id') ?? 0) + 1;
        return 'CB-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Renderiza el HTML imprimible del comprobante.
     *
     * Devuelve el HTML del layout 'impresion' (mismo patrón de Fase 8).
     * En backoffice el usuario imprime a PDF desde el browser.
     * En móvil se abre en WebView.
     *
     * @param  Comprobante  $comprobante
     * @return string HTML renderizado.
     */
    public function obtenerContenidoPdf(Comprobante $comprobante): string
    {
        $comprobante->loadMissing('concepto');

        return view('comprobantes.pdf', [
            'comprobante' => $comprobante,
        ])->render();
    }
}
