<?php

// app/Http/Controllers/LiquidacionController.php

namespace App\Http\Controllers;

use App\Models\LiquidacionAgente;
use App\Models\LiquidacionPuntoVenta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Listado de Liquidaciones mensuales en el backoffice (Art. 21 — Ordenanza SIMETSA).
 *
 * Muestra liquidaciones de agentes y puntos de venta con filtro por tipo y periodo.
 */
class LiquidacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:liquidaciones.ver', ['only' => ['index']]);
    }

    /**
     * Listado unificado de liquidaciones (agentes + puntos de venta).
     *
     * Filtros: tipo (agente|punto_venta), periodo_mes (año-mes).
     */
    public function index(Request $request): View
    {
        $tipo    = $request->query('tipo', 'agente');
        $periodo = $request->query('periodo');  // formato: '2026-06'

        if ($tipo === 'punto_venta') {
            $liquidaciones = LiquidacionPuntoVenta::with('puntoVenta.perfilUsuario')
                ->when($periodo, function ($q) use ($periodo) {
                    [$anio, $mes] = explode('-', $periodo . '-01');
                    return $q->whereYear('periodo_mes', $anio)->whereMonth('periodo_mes', $mes);
                })
                ->orderByDesc('periodo_mes')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();
        } else {
            $liquidaciones = LiquidacionAgente::with('agente.perfilUsuario')
                ->when($periodo, function ($q) use ($periodo) {
                    [$anio, $mes] = explode('-', $periodo . '-01');
                    return $q->whereYear('periodo_mes', $anio)->whereMonth('periodo_mes', $mes);
                })
                ->orderByDesc('periodo_mes')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();
        }

        return view('liquidaciones.index', [
            'liquidaciones' => $liquidaciones,
            'tipoFiltro'    => $tipo,
            'periodoFiltro' => $periodo,
        ]);
    }
}
