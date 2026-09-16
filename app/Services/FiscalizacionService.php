<?php
// app/Services/FiscalizacionService.php

namespace App\Services;

use App\Enums\EstadoTurno;
use App\Enums\TipoIncidente;
use App\Models\AgenteParqueo;
use App\Models\IncidenteCalle;
use App\Models\RecorridoAgente;
use App\Models\TurnoAgente;
use App\Services\integraciones\EcuNovecentonceService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Lógica de fiscalización en calle: turnos, recorridos e incidentes (Art. 38).
 *
 * Responsabilidades:
 *  - Iniciar y finalizar el turno del agente.
 *  - Registrar posiciones GPS durante el turno activo.
 *  - Registrar incidentes de calle y notificar al ECU 911 (stub).
 */
class FiscalizacionService
{
    public function __construct(
        private readonly EcuNovecentonceService $ecu911,
    ) {}

    /**
     * Inicia un nuevo turno para el agente.
     *
     * Art. 38 — el agente debe registrar su ingreso al turno de trabajo.
     *
     * @param  AgenteParqueo  $agente
     * @param  array{observaciones?: string}  $datos
     * @return TurnoAgente
     *
     * @throws DomainException  Si el agente ya tiene un turno activo.
     */
    public function iniciarTurno(AgenteParqueo $agente, array $datos): TurnoAgente
    {
        $turnoActivo = TurnoAgente::where('agente_parqueo_id', $agente->id)
            ->where('estado', EstadoTurno::Iniciado->value)
            ->first();

        if ($turnoActivo) {
            throw new DomainException('Ya tenés un turno activo. Finalizá el turno actual antes de iniciar uno nuevo.');
        }

        return TurnoAgente::create([
            'agente_parqueo_id' => $agente->id,
            'inicio_at'         => now(),
            'estado'            => EstadoTurno::Iniciado->value,
            'observaciones'     => $datos['observaciones'] ?? null,
        ]);
    }

    /**
     * Finaliza el turno activo del agente.
     *
     * @param  TurnoAgente  $turno
     * @param  array{observaciones?: string}  $datos
     * @return TurnoAgente
     *
     * @throws DomainException  Si el turno ya está finalizado.
     */
    public function finalizarTurno(TurnoAgente $turno, array $datos): TurnoAgente
    {
        if (! $turno->estaActivo()) {
            throw new DomainException('Este turno ya fue finalizado.');
        }

        $turno->update([
            'fin_at'        => now(),
            'estado'        => EstadoTurno::Finalizado->value,
            'observaciones' => $datos['observaciones'] ?? $turno->observaciones,
        ]);

        return $turno->fresh();
    }

    /**
     * Recupera el turno activo del agente autenticado, o null si no existe.
     *
     * @param  AgenteParqueo  $agente
     * @return TurnoAgente|null
     */
    public function turnoActivo(AgenteParqueo $agente): ?TurnoAgente
    {
        return TurnoAgente::where('agente_parqueo_id', $agente->id)
            ->where('estado', EstadoTurno::Iniciado->value)
            ->latest('inicio_at')
            ->first();
    }

    /**
     * Registra un punto GPS del recorrido del agente.
     *
     * Solo se puede registrar posición si el agente tiene un turno activo.
     *
     * @param  TurnoAgente  $turno
     * @param  array{latitud: float, longitud: float}  $datos
     * @return RecorridoAgente
     *
     * @throws DomainException  Si el turno no está activo.
     */
    public function registrarPosicion(TurnoAgente $turno, array $datos): RecorridoAgente
    {
        if (! $turno->estaActivo()) {
            throw new DomainException('No podés registrar posición en un turno finalizado.');
        }

        return RecorridoAgente::create([
            'turno_agente_id' => $turno->id,
            'latitud'         => $datos['latitud'],
            'longitud'        => $datos['longitud'],
            'registrado_at'   => now(),
        ]);
    }

    /**
     * Registra un incidente de calle y notifica al ECU 911 (Art. 38.m).
     *
     * @param  TurnoAgente  $turno
     * @param  array{
     *   tipo: string,
     *   descripcion: string,
     *   latitud?: float,
     *   longitud?: float,
     *   foto_evidencia?: \Illuminate\Http\UploadedFile|null,
     * }  $datos
     * @return IncidenteCalle
     *
     * @throws DomainException  Si el turno no está activo.
     */
    public function registrarIncidente(TurnoAgente $turno, array $datos): IncidenteCalle
    {
        if (! $turno->estaActivo()) {
            throw new DomainException('No podés registrar un incidente en un turno finalizado.');
        }

        return DB::transaction(function () use ($turno, $datos) {
            $fotoPath = null;
            if (! empty($datos['foto_evidencia'])) {
                $fotoPath = $datos['foto_evidencia']->store('incidentes', 'public');
            }

            $incidente = IncidenteCalle::create([
                'turno_agente_id' => $turno->id,
                'tipo'            => $datos['tipo'],
                'descripcion'     => $datos['descripcion'],
                'latitud'         => $datos['latitud'] ?? null,
                'longitud'        => $datos['longitud'] ?? null,
                'foto_evidencia'  => $fotoPath,
                'reportado_ecu911'=> false,
                'notificado_at'   => null,
            ]);

            $this->notificarEcuNovecientoonce($incidente);

            return $incidente->fresh();
        });
    }

    /**
     * Notifica el incidente al ECU 911 vía el stub de integración (Art. 38.m).
     *
     * Marca el incidente como reportado si la notificación es aceptada.
     *
     * @param  IncidenteCalle  $incidente
     * @return void
     */
    public function notificarEcuNovecientoonce(IncidenteCalle $incidente): void
    {
        $aceptado = $this->ecu911->notificar($incidente);

        if ($aceptado) {
            $incidente->update([
                'reportado_ecu911' => true,
                'notificado_at'    => now(),
            ]);
        }
    }
}
