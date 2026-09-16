<?php

// app/Http/Controllers/ComprobanteController.php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\Ticket;
use App\Models\Infraccion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Listado de Comprobantes en el backoffice (Art. 19 — Ordenanza SIMETSA).
 */
class ComprobanteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:comprobantes.ver', ['only' => ['index']]);
    }

    /**
     * Listado de comprobantes con filtro por tipo de concepto y fecha de emisión.
     */
    public function index(Request $request): View
    {
        $tipo  = $request->query('tipo');   // 'ticket' | 'infraccion'
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $comprobantes = Comprobante::with('concepto')
            ->when($tipo === 'ticket',     fn ($q) => $q->where('concepto_type', Ticket::class))
            ->when($tipo === 'infraccion', fn ($q) => $q->where('concepto_type', Infraccion::class))
            ->when($desde, fn ($q) => $q->whereDate('fecha_emision', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_emision', '<=', $hasta))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('comprobantes.index', [
            'comprobantes' => $comprobantes,
            'tipoFiltro'   => $tipo,
            'desde'        => $desde,
            'hasta'        => $hasta,
        ]);
    }
}
