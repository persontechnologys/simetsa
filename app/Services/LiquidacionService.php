<?php

// app/Services/LiquidacionService.php

namespace App\Services;

use App\Enums\EstadoTransaccion;
use App\Models\AgenteParqueo;
use App\Models\LiquidacionAgente;
use App\Models\LiquidacionPuntoVenta;
use App\Models\PuntoVenta;
use App\Models\SesionParqueo;
use App\Models\Ticket;
use App\Models\TransaccionPago;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Cálculo de liquidaciones mensuales para agentes y puntos de venta.
 *
 * Art. 21 — Distribución de lo recaudado:
 *   - 60 % al Agente de Parqueo.
 *   - 90 % al Punto de Venta.
 */
class LiquidacionService
{
    /**
     * Calcula y persiste la liquidación mensual de un Agente de Parqueo.
     *
     * Calcula el monto recaudado mediante tickets cuya sesión de parqueo
     * fue iniciada por el agente en el periodo indicado.
     *
     * Art. 21 — 60 % de lo recaudado corresponde al agente.
     *
     * @param  AgenteParqueo  $agente
     * @param  string         $periodo  Fecha en formato 'Y-m-d' (primer día del mes, ej. 2026-06-01).
     * @return LiquidacionAgente
     *
     * @throws DomainException Si ya existe liquidación para ese agente y periodo.
     */
    public function calcularPorAgente(AgenteParqueo $agente, string $periodo): LiquidacionAgente
    {
        $fechaPeriodo = Carbon::parse($periodo)->startOfMonth();

        $existe = LiquidacionAgente::where('agente_parqueo_id', $agente->id)
            ->where('periodo_mes', $fechaPeriodo->toDateString())
            ->exists();

        if ($existe) {
            throw new DomainException(
                "Ya existe una liquidación para el agente {$agente->codigo} en el periodo {$fechaPeriodo->format('m/Y')}."
            );
        }

        // Suma de transacciones completadas de tickets cuya sesión pertenece al agente.
        // Join: sesiones_parqueo.agente_id = $agente->id → ticket.id → transacciones concepto.
        $ticketIds = SesionParqueo::where('agente_id', $agente->id)
            ->whereYear('created_at', $fechaPeriodo->year)
            ->whereMonth('created_at', $fechaPeriodo->month)
            ->pluck('ticket_id');

        $montoBruto = (float) TransaccionPago::where('concepto_type', Ticket::class)
            ->whereIn('concepto_id', $ticketIds)
            ->where('estado', EstadoTransaccion::Completada->value)
            ->sum('monto');

        $porcentaje = 60.00;
        $montoNeto  = round($montoBruto * ($porcentaje / 100), 2);

        return DB::transaction(function () use ($agente, $fechaPeriodo, $montoBruto, $porcentaje, $montoNeto) {
            return LiquidacionAgente::create([
                'agente_parqueo_id' => $agente->id,
                'periodo_mes'       => $fechaPeriodo->toDateString(),
                'monto_bruto'       => $montoBruto,
                'porcentaje'        => $porcentaje,
                'monto_neto'        => $montoNeto,
            ]);
        });
    }

    /**
     * Calcula y persiste la liquidación mensual de un Punto de Venta.
     *
     * Art. 21 — 90 % de lo recaudado corresponde al punto de venta.
     *
     * Nota de deuda técnica: la tabla `tickets` no tiene `punto_venta_id`,
     * por lo que `monto_bruto` se registra como 0 hasta que se implemente
     * ese campo. Ver deuda técnica en CLAUDE.md.
     *
     * @param  PuntoVenta  $puntoVenta
     * @param  string      $periodo  Fecha 'Y-m-d' (primer día del mes).
     * @return LiquidacionPuntoVenta
     *
     * @throws DomainException Si ya existe liquidación para ese PV y periodo.
     */
    public function calcularPorPuntoVenta(PuntoVenta $puntoVenta, string $periodo): LiquidacionPuntoVenta
    {
        $fechaPeriodo = Carbon::parse($periodo)->startOfMonth();

        $existe = LiquidacionPuntoVenta::where('punto_venta_id', $puntoVenta->id)
            ->where('periodo_mes', $fechaPeriodo->toDateString())
            ->exists();

        if ($existe) {
            throw new DomainException(
                "Ya existe una liquidación para el punto de venta {$puntoVenta->codigo} en el periodo {$fechaPeriodo->format('m/Y')}."
            );
        }

        // Deuda técnica: tickets no tiene punto_venta_id; se registra 0 hasta implementarlo.
        $montoBruto = 0.00;
        $porcentaje = 90.00;
        $montoNeto  = 0.00;

        return DB::transaction(function () use ($puntoVenta, $fechaPeriodo, $montoBruto, $porcentaje, $montoNeto) {
            return LiquidacionPuntoVenta::create([
                'punto_venta_id' => $puntoVenta->id,
                'periodo_mes'    => $fechaPeriodo->toDateString(),
                'monto_bruto'    => $montoBruto,
                'porcentaje'     => $porcentaje,
                'monto_neto'     => $montoNeto,
            ]);
        });
    }
}
