<?php

// app/Services/TicketService.php

namespace App\Services;

use App\Enums\EstadoReembolso;
use App\Enums\EstadoTicket;
use App\Enums\EstadoTransaccion;
use App\Enums\MetodoPago;
use App\Enums\ProveedorPago;
use App\Enums\TipoCancelacion;
use App\Models\Cancelacion;
use App\Models\Conductor;
use App\Models\CredencialDiscapacidad;
use App\Models\DiaFeriado;
use App\Models\HorarioOperacion;
use App\Models\Tarifa;
use App\Models\Ticket;
use App\Models\TipoPlaza;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoExonerado;
use App\Models\Zona;
use App\Services\Pagos\PagoManager;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lógica de negocio del sistema de tickets digitales de parqueo tarifado.
 *
 * Reglas legales implementadas:
 *  - Art. 12: horario operativo mar–vie y dom 08:00–18:00; feriados sin costo.
 *  - Art. 13: tolerancia de 5 minutos antes de inmovilizar.
 *  - Art. 14: tiempo máximo de parqueo 2 horas.
 *  - Art. 22: costo $0.25/hora o fracción.
 *  - Art. 26: vehículos CONADIS exonerados de pago.
 *  - Art. 27: vehículos institucionales exonerados (máx 2h).
 */
class TicketService
{
    public function __construct(private readonly PagoManager $pagoManager)
    {
    }

    /**
     * Compra un ticket digital de parqueo para el conductor.
     *
     * Valida horario, feriados, máximo de horas, vehículo activo y calcula el monto.
     *
     * @param  array{
     *     conductor_id: int,
     *     vehiculo_id: int,
     *     zona_id: int,
     *     calle_id: ?int,
     *     horas_compradas: int,
     *     metodo_pago: string,
     *     proveedor: ?string
     * }  $datos
     * @return Ticket
     *
     * @throws DomainException
     */
    public function comprar(array $datos): Ticket
    {
        $ahora = now();

        // Paso 1: Validar horario operativo y feriados (Art. 12)
        $this->validarHorarioYFeriado($ahora);

        // Paso 2: Cargar y validar entidades
        $conductor = Conductor::findOrFail($datos['conductor_id']);
        $vehiculo  = Vehiculo::findOrFail($datos['vehiculo_id']);
        $zona      = Zona::findOrFail($datos['zona_id']);

        if ($vehiculo->conductor_id !== $conductor->id) {
            throw new DomainException('El vehículo no pertenece al conductor autenticado.');
        }

        if ($vehiculo->estado !== Vehiculo::ESTADO_ACTIVO) {
            throw new DomainException('El vehículo no está activo en el sistema.');
        }

        if (! $zona->activo) {
            throw new DomainException("La zona '{$zona->nombre}' no está operativa.");
        }

        // Paso 3: Validar máximo de horas y cruce de jornada (Art. 14)
        $this->validarMaximoHoras((int) $datos['horas_compradas'], $ahora);

        // Paso 4: Verificar que no exista ticket activo para este vehículo
        $tieneTicketVigente = Ticket::where('vehiculo_id', $vehiculo->id)
            ->whereIn('estado', [
                EstadoTicket::Pendiente->value,
                EstadoTicket::Activo->value,
                EstadoTicket::EnTolerancia->value,
            ])
            ->exists();

        if ($tieneTicketVigente) {
            throw new DomainException('El vehículo ya tiene un ticket vigente. Espere a que expire o cancele el anterior.');
        }

        // Paso 5: Calcular monto (incluye verificación de exoneración)
        $calculo = $this->calcularMonto((int) $datos['horas_compradas'], $vehiculo, $zona);

        $proveedorValor = $datos['proveedor'] ?? ProveedorPago::None->value;
        $proveedor      = ProveedorPago::from($proveedorValor);
        $esPagoDigital  = $proveedor->esDigital();
        $esEfectivoOtp  = $proveedor === ProveedorPago::Efectivo;

        return DB::transaction(function () use ($datos, $conductor, $zona, $ahora, $calculo, $proveedor, $esPagoDigital, $esEfectivoOtp) {
            $expira_en = $ahora->copy()->addHours((int) $datos['horas_compradas']);

            // PendientePago: gateway digital o efectivo con OTP — requieren confirmación externa.
            $estadoInicial = ($esPagoDigital || $esEfectivoOtp)
                ? EstadoTicket::PendientePago
                : EstadoTicket::Pendiente;

            $ticket = Ticket::create([
                'codigo'           => Ticket::generarCodigo(),
                'conductor_id'     => $conductor->id,
                'vehiculo_id'      => $datos['vehiculo_id'],
                'zona_id'          => $zona->id,
                'calle_id'         => $datos['calle_id'] ?? null,
                'horas_compradas'  => (int) $datos['horas_compradas'],
                'monto'            => $calculo['monto'],
                'estado'           => $estadoInicial,
                'metodo_pago'      => $datos['metodo_pago'] ?? MetodoPago::Efectivo->value,
                'proveedor'        => $proveedor,
                'es_exonerado'     => $calculo['es_exonerado'],
                'tipo_exoneracion' => $calculo['tipo_exoneracion'],
                'comprado_en'      => $ahora,
                'expira_en'        => $expira_en,
            ]);

            if ($esPagoDigital) {
                $this->pagoManager
                    ->proveedor($proveedor->value)
                    ->iniciarCobro($ticket, ['metodo_pago' => $datos['metodo_pago']]);
            }

            return $ticket;
        });
    }

    /**
     * Cancela voluntariamente un ticket antes de que se inicie la sesión.
     *
     * Solo el conductor propietario puede cancelar (TicketPolicy::cancelar lo verifica).
     * El ticket debe estar en estado 'pendiente' (sin sesión iniciada).
     *
     * @param  Ticket  $ticket
     * @param  User    $por     Usuario que cancela (conductor propietario).
     * @param  string  $motivo
     * @return Cancelacion
     *
     * @throws DomainException
     */
    public function cancelar(Ticket $ticket, User $por, string $motivo): Cancelacion
    {
        if (! $ticket->estado->esCancelable()) {
            throw new DomainException(
                "No se puede cancelar un ticket en estado '{$ticket->estado->etiqueta()}'. " .
                'Solo se pueden cancelar tickets pendientes (sin sesión iniciada).'
            );
        }

        return DB::transaction(function () use ($ticket, $por, $motivo) {
            $estadoReembolso = $this->determinarEstadoReembolso($ticket);

            $ticket->update(['estado' => EstadoTicket::Cancelado]);

            return Cancelacion::create([
                'ticket_id'         => $ticket->id,
                'cancelado_por'     => $por->id,
                'tipo'              => TipoCancelacion::Conductor,
                'motivo'            => $motivo,
                'monto_reembolsado' => 0,
                'estado_reembolso'  => $estadoReembolso,
                'cancelado_en'      => now(),
            ]);
        });
    }

    /**
     * Anula administrativamente un ticket (backoffice — comisario o super_admin).
     *
     * Puede anularse en estado pendiente, activo o en tolerancia.
     * Registra la cancelación con tipo 'admin' y el usuario que la ejecutó.
     *
     * @param  Ticket  $ticket
     * @param  User    $por     Usuario administrador que anula.
     * @param  string  $motivo
     * @return Cancelacion
     *
     * @throws DomainException
     */
    public function anular(Ticket $ticket, User $por, string $motivo): Cancelacion
    {
        if (! $ticket->estado->esAnulable()) {
            throw new DomainException(
                "No se puede anular un ticket en estado '{$ticket->estado->etiqueta()}'. " .
                'Solo pueden anularse tickets pendientes, activos o en tolerancia.'
            );
        }

        return DB::transaction(function () use ($ticket, $por, $motivo) {
            $estadoReembolso = $this->determinarEstadoReembolso($ticket);

            $ticket->update(['estado' => EstadoTicket::Anulado]);

            return Cancelacion::create([
                'ticket_id'         => $ticket->id,
                'cancelado_por'     => $por->id,
                'tipo'              => TipoCancelacion::Admin,
                'motivo'            => $motivo,
                'monto_reembolsado' => 0,
                'estado_reembolso'  => $estadoReembolso,
                'cancelado_en'      => now(),
            ]);
        });
    }

    /**
     * Valida un ticket buscando por placa del vehículo.
     *
     * Devuelve el estado calculado en tiempo real, incluyendo la tolerancia de
     * 5 minutos post-expiración (Art. 13). El estado devuelto puede diferir
     * del persistido en base de datos si el ticket está en transición.
     *
     * @param  string  $placa  Placa del vehículo (mayúsculas o minúsculas).
     * @param  Carbon  $ahora  Momento de la validación (inyectable para tests).
     * @return array{
     *     estado: string,
     *     ticket: ?Ticket,
     *     minutos_restantes: ?int,
     *     en_tolerancia: bool,
     *     tolerancia_expira_en: ?Carbon
     * }
     */
    public function validarPorPlaca(string $placa, Carbon $ahora): array
    {
        $placaUpper = strtoupper(trim($placa));

        // Buscar ticket vigente (pendiente, activo o en tolerancia)
        $ticket = Ticket::whereHas(
            'vehiculo',
            fn ($q) => $q->whereRaw('UPPER(placa) = ?', [$placaUpper])
        )
            ->whereIn('estado', [
                EstadoTicket::Pendiente->value,
                EstadoTicket::Activo->value,
                EstadoTicket::EnTolerancia->value,
            ])
            ->with(['vehiculo', 'zona', 'sesion'])
            ->latest('comprado_en')
            ->first();

        if (! $ticket) {
            // Buscar anulados/expirados recientes para dar contexto al agente
            $reciente = Ticket::whereHas(
                'vehiculo',
                fn ($q) => $q->whereRaw('UPPER(placa) = ?', [$placaUpper])
            )
                ->whereIn('estado', [EstadoTicket::Expirado->value, EstadoTicket::Anulado->value])
                ->where('expira_en', '>=', $ahora->copy()->subMinutes(30))
                ->with(['vehiculo', 'zona'])
                ->latest('comprado_en')
                ->first();

            return [
                'estado'               => 'sin_ticket',
                'ticket'               => $reciente,
                'minutos_restantes'    => null,
                'en_tolerancia'        => false,
                'tolerancia_expira_en' => null,
            ];
        }

        $estadoActual   = $this->calcularEstadoActual($ticket, $ahora);
        $enTolerancia   = $estadoActual === EstadoTicket::EnTolerancia;
        $toleranciaExpira = $enTolerancia
            ? $ticket->expira_en->copy()->addMinutes(5)
            : null;

        $minutosRestantes = $ahora->lt($ticket->expira_en)
            ? (int) $ahora->diffInMinutes($ticket->expira_en, absolute: true)
            : 0;

        return [
            'estado'               => $estadoActual->value,
            'ticket'               => $ticket,
            'minutos_restantes'    => $minutosRestantes,
            'en_tolerancia'        => $enTolerancia,
            'tolerancia_expira_en' => $toleranciaExpira,
        ];
    }

    /**
     * Valida que el sistema esté operativo en el momento dado.
     *
     * Considera días feriados (sin costo, Art. 12), horario por día de semana
     * y que el horario esté activo en el catálogo.
     *
     * @param  Carbon  $ahora
     * @return void
     *
     * @throws DomainException  Si está fuera de horario operativo o es feriado.
     */
    public function validarHorarioYFeriado(Carbon $ahora): void
    {
        // Verificar feriados (Art. 12)
        if (DiaFeriado::esFeriado($ahora)) {
            throw new DomainException(
                'Hoy es día feriado o cívico; el estacionamiento tarifado es libre. No se requiere ticket (Art. 12).'
            );
        }

        $horario = $this->horarioDelDia($ahora);

        if (! $horario) {
            throw new DomainException(
                'El SIMETSA no opera el día de hoy. El estacionamiento es libre (Art. 12).'
            );
        }

        $horaActual = $ahora->format('H:i:s');

        if ($horaActual < $horario->hora_inicio || $horaActual > $horario->hora_fin) {
            $inicio = substr($horario->hora_inicio, 0, 5);
            $fin    = substr($horario->hora_fin, 0, 5);
            throw new DomainException(
                "Fuera del horario operativo ({$inicio}–{$fin}). El estacionamiento es libre fuera de ese horario (Art. 12)."
            );
        }
    }

    /**
     * Calcula el monto a cobrar por el ticket.
     *
     * Si el vehículo tiene exoneración activa (CONADIS o institucional),
     * el monto es $0.00. Si no hay tarifa vigente configurada, usa el
     * valor base de $0.25/hora del Art. 22 como fallback.
     *
     * @param  int      $horas
     * @param  Vehiculo $vehiculo
     * @param  Zona     $zona
     * @return array{monto: float, es_exonerado: bool, tipo_exoneracion: ?string}
     */
    public function calcularMonto(int $horas, Vehiculo $vehiculo, Zona $zona): array
    {
        $tipoExoneracion = $this->verificarExoneracion($vehiculo);

        if ($tipoExoneracion !== null) {
            return [
                'monto'           => 0.00,
                'es_exonerado'    => true,
                'tipo_exoneracion' => $tipoExoneracion,
            ];
        }

        $tarifa    = $this->tarifaVigenteNormal();
        $valorHora = $tarifa ? (float) $tarifa->valor_hora : 0.25;

        if (! $tarifa) {
            Log::warning('TicketService: sin tarifa vigente para plaza normal, usando fallback Art. 22 ($0.25/hora).', [
                'zona_id' => $zona->id,
            ]);
        }

        return [
            'monto'           => round($valorHora * $horas, 2),
            'es_exonerado'    => false,
            'tipo_exoneracion' => null,
        ];
    }

    /**
     * Valida que las horas compradas no excedan el máximo legal ni crucen
     * el cierre de jornada operativa.
     *
     * @param  int     $horas  Horas que el conductor desea comprar (1 o 2).
     * @param  Carbon  $ahora
     * @return void
     *
     * @throws DomainException  Si excede 2 horas o cruza el cierre operativo.
     */
    public function validarMaximoHoras(int $horas, Carbon $ahora): void
    {
        if ($horas < 1 || $horas > 2) {
            throw new DomainException(
                'El tiempo máximo de parqueo es 2 horas (Art. 14). Puede comprar 1 o 2 horas.'
            );
        }
        // La compra es válida si se realiza dentro del horario operativo,
        // aunque el ticket venza después del cierre. La fiscalización
        // se detiene a la hora de cierre (Art. 12).
    }

    // ────────────────────────────────────────────────────────────────────────
    // Privados
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Verifica si el vehículo tiene exoneración vigente.
     *
     * Prioridad: institucional (Art. 27) → CONADIS (Art. 26).
     * Devuelve el código de tipo de exoneración o null si no aplica.
     *
     * @param  Vehiculo  $vehiculo
     * @return string|null  'institucional' | 'conadis' | null
     */
    private function verificarExoneracion(Vehiculo $vehiculo): ?string
    {
        // Art. 27: vehículos institucionales (Policía, Bomberos, FF.AA., Municipal, etc.)
        $exoneradoInstitucional = VehiculoExonerado::whereRaw('UPPER(placa) = ?', [strtoupper($vehiculo->placa)])
            ->where('activo', true)
            ->exists();

        if ($exoneradoInstitucional) {
            return 'institucional';
        }

        // Art. 26: credencial CONADIS aprobada y vigente (personal del conductor, aplica a todos sus vehículos)
        $credencialActiva = $vehiculo->conductor_id && CredencialDiscapacidad::where('conductor_id', $vehiculo->conductor_id)
            ->where('estado', CredencialDiscapacidad::ESTADO_APROBADA)
            ->where(fn ($q) => $q
                ->whereNull('fecha_vencimiento')
                ->orWhereDate('fecha_vencimiento', '>=', today()))
            ->exists();

        if ($credencialActiva) {
            return 'conadis';
        }

        return null;
    }

    /**
     * Calcula el estado real del ticket en función del tiempo actual.
     *
     * No persiste el estado: el comando `simetsa:sincronizar-estados-tickets`
     * usa este método para actualizar la BD periódicamente. También es la
     * autoridad para el agente en calle y para los tests de tolerancia.
     *
     * @param  Ticket  $ticket
     * @param  Carbon  $ahora
     * @return EstadoTicket
     */
    public function calcularEstadoActual(Ticket $ticket, Carbon $ahora): EstadoTicket
    {
        // Estados terminales: no cambian
        if (in_array($ticket->estado, [EstadoTicket::Cancelado, EstadoTicket::Anulado], true)) {
            return $ticket->estado;
        }

        // Dentro del tiempo comprado
        if ($ahora->lte($ticket->expira_en)) {
            return $ticket->sesion ? EstadoTicket::Activo : EstadoTicket::Pendiente;
        }

        // Fuera del tiempo — verificar tolerancia de 5 min (Art. 13)
        $minutosVencido = (int) $ticket->expira_en->diffInMinutes($ahora, absolute: true);

        return $minutosVencido <= 5
            ? EstadoTicket::EnTolerancia
            : EstadoTicket::Expirado;
    }

    /**
     * Determina el estado de reembolso al cancelar/anular un ticket.
     *
     * Si el ticket tiene una transacción completada (pago digital confirmado),
     * el reembolso queda pendiente de gestionar con el gateway.
     * Si fue pagado en efectivo o PagoSimulado, no aplica reembolso digital.
     *
     * @param  Ticket  $ticket
     * @return EstadoReembolso
     */
    private function determinarEstadoReembolso(Ticket $ticket): EstadoReembolso
    {
        if (! $ticket->proveedor?->esDigital()) {
            return EstadoReembolso::NoAplica;
        }

        // Verificar si existe una transacción digital completada
        $tieneTransaccionCompletada = $ticket->transacciones()
            ->where('estado', EstadoTransaccion::Completada->value)
            ->exists();

        return $tieneTransaccionCompletada
            ? EstadoReembolso::Pendiente
            : EstadoReembolso::NoAplica;
    }

    /**
     * Obtiene el horario operativo configurado para el día de semana dado.
     *
     * @param  Carbon  $ahora
     * @return HorarioOperacion|null
     */
    private function horarioDelDia(Carbon $ahora): ?HorarioOperacion
    {
        return HorarioOperacion::where('dia_semana', (int) $ahora->dayOfWeek)
            ->where('activo', true)
            ->first();
    }

    /**
     * Obtiene la tarifa vigente para plazas normales (el tipo de plaza de pago base).
     *
     * La tarifa de la plaza 'normal' es la referencia para el costo estándar
     * de $0.25/hora (Art. 22). Si no hay tarifa configurada, el llamador usa
     * el valor hardcoded del artículo.
     *
     * @return Tarifa|null
     */
    private function tarifaVigenteNormal(): ?Tarifa
    {
        return Tarifa::whereHas(
            'tipoPlaza',
            fn ($q) => $q->where('codigo', TipoPlaza::COD_NORMAL)
        )
            ->where('activo', true)
            ->whereDate('vigente_desde', '<=', today())
            ->where(fn ($q) => $q
                ->whereNull('vigente_hasta')
                ->orWhereDate('vigente_hasta', '>=', today()))
            ->orderByDesc('vigente_desde')
            ->first();
    }
}
