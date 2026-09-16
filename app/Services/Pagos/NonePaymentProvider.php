<?php

// app/Services/Pagos/NonePaymentProvider.php

namespace App\Services\Pagos;

use App\Contracts\Cobrable;
use App\Contracts\PaymentProviderInterface;
use App\Enums\EstadoTransaccion;
use App\Enums\ProveedorPago;
use App\Models\TransaccionPago;
use DomainException;
use Illuminate\Support\Str;

/**
 * Proveedor de pago "ninguno" — efectivo directo, sin gateway externo.
 *
 * La transacción se crea ya en estado Completada porque el pago en efectivo
 * se considera confirmado en el momento del registro (agente o punto de venta
 * verifica el billete físicamente). No hay llamada HTTP ni webhook.
 */
class NonePaymentProvider implements PaymentProviderInterface
{
    public function nombre(): string
    {
        return ProveedorPago::None->value;
    }

    public function estaHabilitado(): bool
    {
        return true;
    }

    public function iniciarCobro(Cobrable $concepto, array $opciones = []): TransaccionPago
    {
        return TransaccionPago::create([
            'concepto_type'      => get_class($concepto),
            'concepto_id'        => $concepto->getKey(),
            'proveedor'          => ProveedorPago::None,
            'monto'              => $concepto->montoCobrable(),
            'moneda'             => 'USD',
            'external_reference' => 'none-' . Str::uuid()->toString(),
            'payment_url'        => null,
            'qr_payload'         => null,
            'estado'             => EstadoTransaccion::Completada,
            'payload_request'    => $opciones,
        ]);
    }

    public function consultarEstado(TransaccionPago $transaccion): TransaccionPago
    {
        return $transaccion;
    }

    public function procesarWebhook(array $payload, string $firma): TransaccionPago
    {
        throw new DomainException('El proveedor "none" no recibe webhooks.');
    }
}
