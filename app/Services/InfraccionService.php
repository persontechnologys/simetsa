<?php

// app/Services/InfraccionService.php

namespace App\Services;

use App\Enums\EstadoInfraccion;
use App\Enums\EstadoInmovilizacion;
use App\Enums\TipoInfraccion;
use App\Models\AgenteParqueo;
use App\Models\Conductor;
use App\Models\Infraccion;
use App\Models\Inmovilizacion;
use App\Models\Parametro;
use App\Models\TransaccionPago;
use App\Models\User;
use App\Services\Pagos\PagoManager;
use DomainException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lógica de negocio del módulo de infracciones y sanciones.
 *
 * Reglas legales implementadas:
 *  - Art. 15: inmovilización por ausencia de ticket o tiempo excedido.
 *  - Art. 17: 7 tipos de infracción.
 *  - Art. 18: restricciones generales de uso.
 *  - Art. 28: multa escalonada por tiempo excedido (2 / 4 / 8 % SBU).
 *  - Art. 29: multas fijas por tipo (2 / 20 % SBU).
 *  - Art. 30: agresión al agente → 50 % SBU.
 */
class InfraccionService
{
    public function __construct(
        private readonly PagoManager                    $pagoManager,
        private readonly NotificacionInfraccionService  $notificacionService,
    ) {
    }
    // ── Cálculo de multa ──────────────────────────────────────────────────────

    /**
     * Calcula el monto de la multa en USD a partir del tipo de infracción y el SBU.
     *
     * Para TiempoExcedido aplica la tabla escalonada del Art. 28:
     *   - 6  a 60  minutos → 2 % SBU
     *   - 61 a 120 minutos → 4 % SBU
     *   - > 120 minutos    → 8 % SBU
     *
     * Para los demás tipos usa el porcentaje fijo de Arts. 29 y 30.
     * NegarPago (Art. 17.g) no tiene cargo económico → retorna 0.
     *
     * @param  TipoInfraccion  $tipo
     * @param  int             $minutosExcedidos  Solo requerido para TiempoExcedido.
     * @param  float           $sbu               SBU vigente en USD.
     * @return float           Monto redondeado a 2 decimales.
     *
     * @throws DomainException  Si TiempoExcedido con minutos < 6 (Art. 28: la tolerancia es 5 min).
     */
    public function calcularMulta(TipoInfraccion $tipo, int $minutosExcedidos = 0, float $sbu = 0.0): float
    {
        if ($tipo === TipoInfraccion::TiempoExcedido) {
            // Art. 28: tolerancia de 5 minutos (Art. 13), multa desde el minuto 6
            if ($minutosExcedidos < 6) {
                throw new DomainException(
                    'El tiempo excedido debe ser de al menos 6 minutos para generar infracción (Art. 28).'
                );
            }

            $porcentaje = match (true) {
                $minutosExcedidos <= 60  => 2.0,   // Art. 28: 6–60 min
                $minutosExcedidos <= 120 => 4.0,   // Art. 28: 61–120 min
                default                  => 8.0,   // Art. 28: > 120 min
            };

            return round($sbu * $porcentaje / 100, 2);
        }

        $porcentaje = $tipo->porcentajeSbu();

        if ($porcentaje === null) {
            // Fallback defensivo: no debería ocurrir con el enum actual
            return 0.0;
        }

        return round($sbu * $porcentaje / 100, 2);
    }

    // ── Operaciones ───────────────────────────────────────────────────────────

    /**
     * Registra una nueva infracción y calcula la multa automáticamente.
     *
     * @param  array{
     *     placa: string,
     *     tipo_infraccion: TipoInfraccion,
     *     zona_id: int,
     *     calle_id: ?int,
     *     ticket_id: ?int,
     *     conductor_id: ?int,
     *     minutos_excedidos: ?int,
     *     descripcion: ?string,
     *     foto_evidencia: ?string,
     *     latitud: ?float,
     *     longitud: ?float,
     * }  $datos
     * @param  AgenteParqueo  $agente  Agente que registra (Art. 38.l).
     * @return Infraccion
     *
     * @throws DomainException
     */
    public function registrar(array $datos, AgenteParqueo $agente): Infraccion
    {
        if ($agente->estado !== AgenteParqueo::ESTADO_ACTIVO) {
            throw new DomainException('El agente no está activo y no puede registrar infracciones.');
        }

        $tipo = $datos['tipo_infraccion'] instanceof TipoInfraccion
            ? $datos['tipo_infraccion']
            : TipoInfraccion::from($datos['tipo_infraccion']);

        $minutos = (int) ($datos['minutos_excedidos'] ?? 0);

        if ($tipo === TipoInfraccion::TiempoExcedido && $minutos < 6) {
            throw new DomainException(
                'Para infracción por tiempo excedido debe indicar al menos 6 minutos (Art. 28).'
            );
        }

        // Snapshot del SBU vigente al momento de la infracción (Art. 28-30)
        $sbu   = (float) Parametro::obtener('sbu_vigente', 460.00);
        $monto = $this->calcularMulta($tipo, $minutos, $sbu);

        $infraccion = DB::transaction(function () use ($datos, $agente, $tipo, $monto, $sbu) {
            return Infraccion::create([
                'placa'             => $datos['placa'],
                'conductor_id'      => $datos['conductor_id'] ?? null,
                'zona_id'           => $datos['zona_id'],
                'calle_id'          => $datos['calle_id'] ?? null,
                'agente_parqueo_id' => $agente->id,
                'ticket_id'         => $datos['ticket_id'] ?? null,
                'tipo_infraccion'   => $tipo,
                'estado'            => EstadoInfraccion::Pendiente,
                'monto_multa'       => $monto,
                'sbu_vigente'       => $sbu,
                'minutos_excedidos' => $tipo === TipoInfraccion::TiempoExcedido
                    ? (int) ($datos['minutos_excedidos'] ?? 0)
                    : null,
                'descripcion'   => $datos['descripcion'] ?? null,
                'foto_evidencia'=> $datos['foto_evidencia'] ?? null,
                'latitud'       => $datos['latitud'] ?? null,
                'longitud'      => $datos['longitud'] ?? null,
            ]);
        });

        // Boleta digital best-effort: si falla la notificación, no interrumpe el registro
        if ($infraccion->conductor_id !== null) {
            try {
                $this->notificacionService->notificar($infraccion);
            } catch (\Throwable $e) {
                Log::warning('InfraccionService: notificacion al conductor fallida.', [
                    'infraccion_id' => $infraccion->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        return $infraccion;
    }

    /**
     * Coloca el candado inmovilizador sobre el vehículo infraccionado (Art. 15).
     *
     * @param  Infraccion    $infraccion  Debe estar en estado pendiente.
     * @param  AgenteParqueo $agente      Agente que coloca el candado.
     * @param  array{
     *     foto_candado: ?string,
     *     notas: ?string,
     * }  $datos
     * @return Inmovilizacion
     *
     * @throws DomainException
     */
    public function inmovilizar(Infraccion $infraccion, AgenteParqueo $agente, array $datos = []): Inmovilizacion
    {
        if ($infraccion->estado !== EstadoInfraccion::Pendiente) {
            throw new DomainException(
                'Solo se puede inmovilizar un vehículo con infracción pendiente de pago.'
            );
        }

        if ($infraccion->inmovilizacion !== null) {
            throw new DomainException(
                'Este vehículo ya tiene un candado inmovilizador registrado.'
            );
        }

        if ($agente->estado !== AgenteParqueo::ESTADO_ACTIVO) {
            throw new DomainException('El agente no está activo y no puede inmovilizar vehículos.');
        }

        return DB::transaction(function () use ($infraccion, $agente, $datos) {
            return Inmovilizacion::create([
                'infraccion_id'    => $infraccion->id,
                'agente_parqueo_id'=> $agente->id,
                'estado'           => EstadoInmovilizacion::Activa,
                'foto_candado'     => $datos['foto_candado'] ?? null,
                'notas'            => $datos['notas'] ?? null,
                'inmovilizada_en'  => now(),
            ]);
        });
    }

    /**
     * Retira el candado inmovilizador tras verificar que la infracción fue pagada (Art. 15).
     *
     * Solo puede ejecutarse después de que Infraccion::acreditar() marcó la infracción
     * como pagada y liberó automáticamente la inmovilización. Este método es para
     * el caso de liberación manual por parte del comisario (anulación o error).
     *
     * @param  Inmovilizacion  $inmovilizacion
     * @param  string|null     $motivo  Requerido si la infracción no está pagada (liberación forzada).
     * @return Inmovilizacion
     *
     * @throws DomainException
     */
    public function liberar(Inmovilizacion $inmovilizacion, ?string $motivo = null): Inmovilizacion
    {
        if (! $inmovilizacion->estaActiva()) {
            throw new DomainException('La inmovilización no está activa.');
        }

        $infraccion = $inmovilizacion->infraccion;

        // Verificar que la infracción fue pagada O que se proporciona motivo de liberación forzada
        if ($infraccion->estado !== EstadoInfraccion::Pagada && empty($motivo)) {
            throw new DomainException(
                'El candado solo puede retirarse tras el pago de la infracción (Art. 15), ' .
                'o indicando un motivo de liberación administrativa.'
            );
        }

        return DB::transaction(function () use ($inmovilizacion, $motivo) {
            $inmovilizacion->update([
                'estado'      => EstadoInmovilizacion::Liberada,
                'liberada_en' => now(),
                'notas'       => $motivo
                    ? ($inmovilizacion->notas ? $inmovilizacion->notas . ' | ' . $motivo : $motivo)
                    : $inmovilizacion->notas,
            ]);

            return $inmovilizacion->fresh();
        });
    }

    /**
     * Anula administrativamente una infracción (solo comisario / super_admin).
     *
     * Si existe una inmovilización activa, la anula también.
     *
     * @param  Infraccion  $infraccion
     * @param  User        $usuario      Usuario que ejecuta la anulación.
     * @param  string      $motivo
     * @return Infraccion
     *
     * @throws DomainException
     */
    public function anular(Infraccion $infraccion, User $usuario, string $motivo): Infraccion
    {
        if (! $infraccion->estado->esAnulable()) {
            throw new DomainException(
                "No se puede anular una infracción en estado '{$infraccion->estado->etiqueta()}'."
            );
        }

        if (empty(trim($motivo))) {
            throw new DomainException('El motivo de anulación es obligatorio.');
        }

        return DB::transaction(function () use ($infraccion, $usuario, $motivo) {
            // Si hay inmovilización activa, anuladarla también
            $infraccion->load('inmovilizacion');
            if ($infraccion->inmovilizacion && $infraccion->inmovilizacion->estaActiva()) {
                $infraccion->inmovilizacion->update([
                    'estado'           => EstadoInmovilizacion::Anulada,
                    'motivo_anulacion' => $motivo,
                    'anulada_por'      => $usuario->id,
                ]);
            }

            $infraccion->update([
                'estado'           => EstadoInfraccion::Anulada,
                'motivo_anulacion' => $motivo,
                'anulada_por'      => $usuario->id,
                'anulada_en'       => now(),
            ]);

            return $infraccion->fresh();
        });
    }

    // ── Conductor ─────────────────────────────────────────────────────────────

    /**
     * Devuelve el historial paginado de infracciones visibles para un conductor.
     *
     * Incluye infracciones donde:
     * - conductor_id coincide con el conductor, O
     * - la placa coincide con alguno de sus vehículos registrados.
     *
     * @param  Conductor  $conductor
     * @param  int        $porPagina
     * @return LengthAwarePaginator
     */
    public function historialConductor(Conductor $conductor, int $porPagina = 15): LengthAwarePaginator
    {
        $placas = $conductor->vehiculos()->pluck('placa')->map(fn ($p) => strtoupper($p))->all();

        return Infraccion::where(function ($q) use ($conductor, $placas) {
            $q->where('conductor_id', $conductor->id);
            if (! empty($placas)) {
                $q->orWhereIn('placa', $placas);
            }
        })
            ->with(['zona', 'agente', 'inmovilizacion'])
            ->orderByDesc('created_at')
            ->paginate($porPagina);
    }

    /**
     * Inicia el cobro de una multa a través de un gateway de pagos.
     *
     * @param  Infraccion  $infraccion
     * @param  Conductor   $conductor    El conductor que solicita el pago (para verificar ownership).
     * @param  string      $proveedor    Nombre del proveedor (ej. 'deuna').
     * @return TransaccionPago
     *
     * @throws DomainException
     */
    public function iniciarPago(Infraccion $infraccion, Conductor $conductor, string $proveedor): TransaccionPago
    {
        if ($infraccion->estado !== EstadoInfraccion::Pendiente) {
            throw new DomainException(
                "No se puede pagar una infracción en estado '{$infraccion->estado->etiqueta()}'."
            );
        }

        // Verificar que la multa corresponde al conductor (por conductor_id o por placa de su vehículo)
        $esDelConductor = $infraccion->conductor_id === $conductor->id
            || $conductor->vehiculos()->where('placa', $infraccion->placa)->exists();

        if (! $esDelConductor) {
            throw new DomainException('No puede pagar una multa que no corresponde a sus vehículos.');
        }

        return DB::transaction(function () use ($infraccion, $conductor, $proveedor) {
            return $this->pagoManager
                ->proveedor($proveedor)
                ->iniciarCobro($infraccion, [
                    'conductor_id' => $conductor->id,
                    'concepto'     => 'multa',
                ]);
        });
    }
}
