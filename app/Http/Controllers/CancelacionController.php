<?php

// app/Http/Controllers/CancelacionController.php

namespace App\Http\Controllers;

use App\Enums\TipoCancelacion;
use App\Models\Cancelacion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Backoffice de supervisión de cancelaciones y anulaciones de tickets.
 *
 * Solo lectura: index + show. Las cancelaciones se originan desde la app del
 * conductor o desde TicketController::anular (vía TicketService).
 *
 * Permiso requerido: cancelaciones.ver.
 */
class CancelacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:cancelaciones.ver', ['only' => ['index', 'show']]);
    }

    /**
     * Lista cancelaciones con filtros: fecha, tipo, placa del ticket.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = Cancelacion::with(['ticket', 'canceladoPorUsuario.perfil'])
            ->when($request->tipo, fn ($q, $t) =>
                $q->where('tipo', $t)
            )
            ->when($request->placa, fn ($q, $p) =>
                $q->whereHas('ticket', fn ($qt) =>
                    $qt->where('placa', strtoupper(trim($p)))
                )
            )
            ->when($request->fecha_desde, fn ($q, $f) =>
                $q->whereDate('cancelado_en', '>=', $f)
            )
            ->when($request->fecha_hasta, fn ($q, $f) =>
                $q->whereDate('cancelado_en', '<=', $f)
            )
            ->orderByDesc('cancelado_en');

        return view('cancelaciones.index', [
            'cancelaciones' => $query->paginate(25)->withQueryString(),
            'tipos'         => collect(TipoCancelacion::cases())->mapWithKeys(
                fn ($t) => [$t->value => $t->etiqueta()]
            )->all(),
        ]);
    }

    /**
     * Detalle de una cancelación con el ticket asociado.
     *
     * @param  \App\Models\Cancelacion  $cancelacion
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Cancelacion $cancelacion): View
    {
        $cancelacion->load(['ticket.conductor.user.perfil', 'canceladoPorUsuario.perfil']);

        return view('cancelaciones.show', compact('cancelacion'));
    }
}
