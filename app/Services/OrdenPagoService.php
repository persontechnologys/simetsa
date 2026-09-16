<?php

// app/Services/OrdenPagoService.php

namespace App\Services;

use App\Enums\EstadoOrdenPago;
use App\Models\Infraccion;
use App\Models\OrdenPago;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio para Órdenes de Pago (Art. 28 — Ordenanza SIMETSA).
 *
 * Una OrdenPago formaliza el cobro de una multa antes de que el conductor
 * pueda realizar el pago a través del gateway.
 */
class OrdenPagoService
{
    /**
     * Genera una nueva Orden de Pago para una infracción.
     *
     * Art. 28 — solo puede existir una orden vigente (estado pendiente) por infracción.
     *
     * @throws DomainException Si ya existe una orden pendiente.
     */
    public function generar(Infraccion $infraccion, User $generadaPor): OrdenPago
    {
        $existe = OrdenPago::where('infraccion_id', $infraccion->id)
            ->where('estado', EstadoOrdenPago::Pendiente->value)
            ->exists();

        if ($existe) {
            throw new DomainException(
                'Ya existe una Orden de Pago vigente para esta infracción. Anúlela antes de generar una nueva.'
            );
        }

        $diasVencimiento = (int) config('simetsa.orden_pago_dias_vencimiento', 30);

        return DB::transaction(function () use ($infraccion, $generadaPor, $diasVencimiento) {
            return OrdenPago::create([
                'infraccion_id' => $infraccion->id,
                'numero_orden'  => $this->generarNumero(),
                'monto'         => $infraccion->monto_multa,
                'estado'        => EstadoOrdenPago::Pendiente,
                'vence_at'      => now()->addDays($diasVencimiento),
                'generada_por'  => $generadaPor->id,
            ]);
        });
    }

    /**
     * Anula una Orden de Pago pendiente.
     *
     * @throws DomainException Si la orden no está en estado pendiente.
     */
    public function anular(OrdenPago $ordenPago, string $motivo): OrdenPago
    {
        if ($ordenPago->estado !== EstadoOrdenPago::Pendiente) {
            throw new DomainException(
                'Solo se puede anular una Orden de Pago en estado pendiente.'
            );
        }

        $ordenPago->update([
            'estado'           => EstadoOrdenPago::Anulada,
            'motivo_anulacion' => $motivo,
        ]);

        return $ordenPago->fresh();
    }

    /**
     * Marca como vencidas las órdenes cuya fecha de vencimiento ya pasó.
     *
     * Llamar desde un comando artisan programado diariamente.
     *
     * @return int Número de órdenes actualizadas.
     */
    public function verificarVencimientos(): int
    {
        return OrdenPago::where('estado', EstadoOrdenPago::Pendiente->value)
            ->where('vence_at', '<', now())
            ->update(['estado' => EstadoOrdenPago::Vencida->value]);
    }

    /** Genera el número correlativo de la orden (OP-0001). */
    private function generarNumero(): string
    {
        $siguiente = (OrdenPago::max('id') ?? 0) + 1;
        return 'OP-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
    }
}
