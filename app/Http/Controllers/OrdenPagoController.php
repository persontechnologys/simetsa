<?php

// app/Http/Controllers/OrdenPagoController.php

namespace App\Http\Controllers;

use App\Enums\EstadoOrdenPago;
use App\Models\OrdenPago;
use App\Services\OrdenPagoService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gestión de Órdenes de Pago en el backoffice (Art. 28 — Ordenanza SIMETSA).
 *
 * Solo lectura + acción de anulación. La generación ocurre vía API.
 */
class OrdenPagoController extends Controller
{
    public function __construct(private readonly OrdenPagoService $ordenPagoService)
    {
        $this->middleware('permission:ordenes_pago.ver',     ['only' => ['index', 'show']]);
        $this->middleware('permission:ordenes_pago.generar', ['only' => ['anular']]);
    }

    /**
     * Listado de órdenes de pago con filtros de estado y placa.
     */
    public function index(Request $request): View
    {
        $estado = $request->query('estado');
        $placa  = $request->query('placa');

        $ordenes = OrdenPago::with(['infraccion', 'generadaPor'])
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->when($placa,  fn ($q) => $q->whereHas('infraccion', fn ($qi) => $qi->where('placa', strtoupper($placa))))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('ordenes-pago.index', [
            'ordenes'      => $ordenes,
            'estadoFiltro' => $estado,
            'placaFiltro'  => $placa,
            'estados'      => EstadoOrdenPago::cases(),
        ]);
    }

    /**
     * Detalle de una orden de pago.
     */
    public function show(OrdenPago $ordenPago): View
    {
        $ordenPago->load(['infraccion.agente.perfilUsuario', 'generadaPor']);

        return view('ordenes-pago.show', ['ordenPago' => $ordenPago]);
    }

    /**
     * Anula una orden de pago pendiente.
     */
    public function anular(Request $request, OrdenPago $ordenPago): RedirectResponse
    {
        $datos = $request->validate([
            'motivo_anulacion' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->ordenPagoService->anular($ordenPago, $datos['motivo_anulacion']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Orden {$ordenPago->numero_orden} anulada.");
    }
}
