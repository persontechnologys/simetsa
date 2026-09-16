<?php
// app/Services/PagoEfectivoService.php

namespace App\Services;

use App\Contracts\Cobrable;
use App\Enums\EstadoInfraccion;
use App\Enums\EstadoTicket;
use App\Enums\EstadoTransaccion;
use App\Enums\ProveedorPago;
use App\Models\Conductor;
use App\Models\Infraccion;
use App\Models\Ticket;
use App\Models\TransaccionPago;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Servicio de pago en efectivo con confirmación OTP (Art. 14, 19 — Ordenanza SIMETSA).
 *
 * Implementa el flujo bidireccional:
 *  1. Conductor solicita → obtiene código OTP de 6 caracteres válido 10 minutos.
 *  2. Conductor muestra el código al agente y entrega el efectivo.
 *  3. Agente ingresa el código en su app → se acredita el pago y se genera el comprobante.
 *
 * El OTP se almacena en la caché de Laravel (TTL 10 min, single-use).
 * La clave de caché es: "otp_ef:{$otp_codigo}" → $transaccion_id
 */
class PagoEfectivoService
{
    private const TTL_MINUTOS = 10;
    private const CACHE_PREFIX = 'otp_ef:';

    public function __construct(private readonly ComprobanteService $comprobanteService)
    {
    }

    /**
     * Solicita un código OTP para pagar un concepto (ticket o infracción) en efectivo.
     *
     * Crea una TransaccionPago en estado Pendiente y devuelve el código OTP
     * que el conductor le muestra al agente para que éste confirme el cobro.
     *
     * @see Art. 14 Ordenanza SIMETSA — pago al agente en calle.
     *
     * @param  string  $tipo         'ticket' | 'infraccion'
     * @param  int     $conceptoId   ID del concepto a pagar.
     * @param  User    $conductor    Usuario conductor autenticado.
     * @return array{otp_codigo: string, transaccion_id: int, monto: float, descripcion: string, expira_en: string}
     *
     * @throws \DomainException  Si el concepto no es pagable o no pertenece al conductor.
     */
    public function solicitarOtp(string $tipo, int $conceptoId, User $conductor): array
    {
        $cobrable = $this->resolverCobrable($tipo, $conceptoId);

        $this->validarPagable($cobrable, $tipo);
        $this->validarPropiedad($cobrable, $conductor, $tipo);

        return DB::transaction(function () use ($cobrable, $tipo, $conceptoId) {
            // Cancelar OTP previo del mismo concepto (evitar OTPs huérfanos).
            $this->cancelarOtpPrevio($tipo, $conceptoId);

            $transaccion = TransaccionPago::create([
                'concepto_type'      => get_class($cobrable),
                'concepto_id'        => $cobrable->getKey(),
                'proveedor'          => ProveedorPago::Efectivo,
                'monto'              => $cobrable->montoCobrable(),
                'moneda'             => 'USD',
                'external_reference' => 'efectivo-' . Str::ulid(),
                'estado'             => EstadoTransaccion::Pendiente,
                'payload_request'    => ['tipo' => $tipo, 'concepto_id' => $conceptoId],
            ]);

            $otp      = strtoupper(Str::random(6));
            $expiraEn = now()->addMinutes(self::TTL_MINUTOS);

            // Almacena OTP → transaccion_id, con TTL de 10 minutos.
            Cache::put(self::CACHE_PREFIX . $otp, $transaccion->id, $expiraEn);

            // Índice inverso para cancelar el OTP previo si se solicita otro.
            Cache::put("otp_ef_concepto:{$tipo}:{$cobrable->getKey()}", $otp, $expiraEn);

            Log::info('Pago efectivo OTP generado', [
                'transaccion_id' => $transaccion->id,
                'tipo'           => $tipo,
                'concepto_id'    => $cobrable->getKey(),
                'monto'          => $cobrable->montoCobrable(),
            ]);

            return [
                'otp_codigo'     => $otp,
                'transaccion_id' => $transaccion->id,
                'monto'          => (float) $cobrable->montoCobrable(),
                'descripcion'    => $cobrable->descripcionCobro(),
                'expira_en'      => $expiraEn->toIso8601String(),
            ];
        });
    }

    /**
     * Confirma el código OTP y acredita el pago (Art. 14, 19).
     *
     * Solo puede ejecutarlo un agente de parqueo autenticado.
     * Invalida el OTP inmediatamente (single-use).
     *
     * @param  string  $otpCodigo  Código de 6 caracteres ingresado por el agente.
     * @param  User    $agente     Usuario agente autenticado.
     * @return array{transaccion: TransaccionPago, comprobante_id: int|null, numero_comprobante: string|null}
     *
     * @throws \DomainException  Si el código es inválido, expirado o ya fue usado.
     */
    public function confirmarOtp(string $otpCodigo, User $agente): array
    {
        $codigo       = strtoupper(trim($otpCodigo));
        $transaccionId = Cache::get(self::CACHE_PREFIX . $codigo);

        if (! $transaccionId) {
            throw new DomainException('Código inválido o expirado. Solicite uno nuevo al conductor.');
        }

        $transaccion = TransaccionPago::find($transaccionId);

        if (! $transaccion || $transaccion->estado !== EstadoTransaccion::Pendiente) {
            Cache::forget(self::CACHE_PREFIX . $codigo);
            throw new DomainException('La transacción ya fue procesada o no existe.');
        }

        return DB::transaction(function () use ($transaccion, $agente, $codigo) {
            // Invalida el OTP de inmediato (single-use).
            Cache::forget(self::CACHE_PREFIX . $codigo);

            $transaccion->update([
                'estado'              => EstadoTransaccion::Completada,
                'callback_recibido_en' => now(),
                'payload_response'    => [
                    'confirmado_por'  => $agente->id,
                    'confirmado_en'   => now()->toIso8601String(),
                ],
            ]);

            // Acreditar el concepto cobrado (Ticket o Infraccion).
            $transaccion->concepto->acreditar($transaccion);

            // Generar comprobante automáticamente (Art. 19).
            $comprobante = null;
            try {
                $comprobante = $this->comprobanteService->generar(
                    $transaccion->concepto,
                    $transaccion,
                );
            } catch (\Throwable $e) {
                Log::error('Pago efectivo: error al generar comprobante', [
                    'transaccion_id' => $transaccion->id,
                    'error'          => $e->getMessage(),
                ]);
            }

            Log::info('Pago efectivo OTP confirmado', [
                'transaccion_id' => $transaccion->id,
                'agente_id'      => $agente->id,
                'monto'          => $transaccion->monto,
            ]);

            return [
                'transaccion'       => $transaccion->fresh(),
                'comprobante_id'    => $comprobante?->id,
                'numero_comprobante' => $comprobante?->numero,
            ];
        });
    }

    /**
     * Resuelve el Cobrable a partir del tipo y el ID.
     */
    private function resolverCobrable(string $tipo, int $id): Cobrable
    {
        return match ($tipo) {
            'ticket'    => Ticket::findOrFail($id),
            'infraccion' => Infraccion::findOrFail($id),
            default     => throw new DomainException("Tipo de concepto inválido: {$tipo}."),
        };
    }

    /**
     * Valida que el concepto esté en un estado que permita el pago.
     */
    private function validarPagable(Cobrable $cobrable, string $tipo): void
    {
        if ($tipo === 'ticket') {
            /** @var Ticket $cobrable */
            if ($cobrable->estado !== EstadoTicket::PendientePago) {
                throw new DomainException(
                    "El ticket no está pendiente de pago (estado: {$cobrable->estado->value})."
                );
            }
        }

        if ($tipo === 'infraccion') {
            /** @var Infraccion $cobrable */
            if ($cobrable->estado !== EstadoInfraccion::Pendiente) {
                throw new DomainException(
                    "La infracción no está pendiente de pago (estado: {$cobrable->estado->value})."
                );
            }
        }
    }

    /**
     * Valida que el concepto pertenece al conductor autenticado.
     *
     * Para infracciones con conductor_id null (placa sin conductor registrado)
     * la validación se omite — cualquier conductor puede iniciar el pago.
     */
    private function validarPropiedad(Cobrable $cobrable, User $conductor, string $tipo): void
    {
        if ($tipo === 'ticket') {
            /** @var Ticket $cobrable */
            if ($cobrable->conductor_id !== null) {
                $conductorPerfil = Conductor::where('user_id', $conductor->id)->first();
                if (! $conductorPerfil || $cobrable->conductor_id !== $conductorPerfil->id) {
                    throw new DomainException('No tenés acceso a este ticket.');
                }
            }
        }

        if ($tipo === 'infraccion') {
            /** @var Infraccion $cobrable */
            if ($cobrable->conductor_id !== null) {
                $conductorPerfil = Conductor::where('user_id', $conductor->id)->first();
                if (! $conductorPerfil || $cobrable->conductor_id !== $conductorPerfil->id) {
                    throw new DomainException('No tenés acceso a esta infracción.');
                }
            }
        }
    }

    /**
     * Cancela el OTP previo del mismo concepto si existe (evita OTPs huérfanos).
     */
    private function cancelarOtpPrevio(string $tipo, int $conceptoId): void
    {
        $clave    = "otp_ef_concepto:{$tipo}:{$conceptoId}";
        $otpPrevio = Cache::get($clave);

        if ($otpPrevio) {
            Cache::forget(self::CACHE_PREFIX . $otpPrevio);
            Cache::forget($clave);
        }
    }
}
