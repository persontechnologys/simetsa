<?php
// app/Http/Controllers/TurnoAgenteController.php

namespace App\Http\Controllers;

use App\Enums\EstadoTurno;
use App\Models\AgenteParqueo;
use App\Models\TurnoAgente;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Supervisión de turnos del agente de parqueo en el backoffice.
 *
 * Solo lectura. Permisos gestionados en el constructor via $this->middleware().
 * Art. 38 — Ordenanza SIMETSA.
 */
class TurnoAgenteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:turnos.ver', ['only' => ['index', 'show']]);
    }

    /**
     * Listado de turnos con filtros: agente, estado, fecha.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $agentes = AgenteParqueo::orderBy('codigo')->get(['id', 'codigo']);

        $turnos = TurnoAgente::query()
            ->with('agente')
            ->when($request->agente_id, fn ($q) => $q->where('agente_parqueo_id', $request->agente_id))
            ->when($request->estado,    fn ($q) => $q->where('estado', $request->estado))
            ->when($request->fecha_desde, fn ($q) => $q->whereDate('inicio_at', '>=', $request->fecha_desde))
            ->when($request->fecha_hasta, fn ($q) => $q->whereDate('inicio_at', '<=', $request->fecha_hasta))
            ->orderByDesc('inicio_at')
            ->paginate(20)
            ->withQueryString();

        return view('turnos.index', [
            'turnos'   => $turnos,
            'agentes'  => $agentes,
            'estados'  => EstadoTurno::cases(),
        ]);
    }

    /**
     * Detalle de un turno: recorrido en mapa Leaflet e incidentes.
     *
     * @param  \App\Models\TurnoAgente  $turno
     * @return \Illuminate\Contracts\View\View
     */
    public function show(TurnoAgente $turno): View
    {
        $turno->load(['agente', 'recorridos', 'incidentes']);

        $puntosGps = $turno->recorridos
            ->map(fn ($r) => [$r->latitud, $r->longitud])
            ->toArray();

        return view('turnos.show', [
            'turno'    => $turno,
            'puntosGps'=> $puntosGps,
        ]);
    }
}
