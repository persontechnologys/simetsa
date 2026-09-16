<?php

// app/Services/Pagos/ManualPaymentProvider.php

namespace App\Services\Pagos;

use App\Contracts\Cobrable;
use App\Contracts\PaymentProviderInterface;
use App\Enums\EstadoTransaccion;
use App\Enums\ProveedorPago;
use App\Models\TransaccionPago;
use DomainException;
use Illuminate\Support\Str;

/**
 * Proveedor de pago manual — solo para entornos local, testing y staging.
 *
 * Confirma el cobro al instante sin llamada HTTP, útil para pruebas de integración
 * de extremo a extremo de la app móvil sin necesidad de un gateway real.
 */
class ManualPaymentProvider implements PaymentProviderInterface
{
    public function nombre(): string
    {
        return ProveedorPago::Manual->value;
    }

    public function estaHabilitado(): bool
    {
        return in_array(app()->environment(), ['local', 'testing', 'staging'], true);
    }

    public function iniciarCobro(Cobrable $concepto, array $opciones = []): TransaccionPago
    {
        if (! $this->estaHabilitado()) {
            throw new DomainException('El proveedor "manual" solo está disponible en entornos de prueba.');
        }

        return TransaccionPago::create([
            'concepto_type'      => get_class($concepto),
            'concepto_id'        => $concepto->getKey(),
            'proveedor'          => ProveedorPago::Manual,
            'monto'              => $concepto->montoCobrable(),
            'moneda'             => 'USD',
            'external_reference' => 'manual-' . Str::uuid()->toString(),
            'payment_url'        => null,
            'qr_payload'         => null,
            'estado'             => EstadoTransaccion::Completada,
            'payload_request'    => array_merge($opciones, ['modo' => 'manual']),
        ]);
    }

    public function consultarEstado(TransaccionPago $transaccion): TransaccionPago
    {
        return $transaccion;
    }

    public function procesarWebhook(array $payload, string $firma): TransaccionPago
    {
        throw new DomainException('El proveedor "manual" no recibe webhooks.');
    }
}
