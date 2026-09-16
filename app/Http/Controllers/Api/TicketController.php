<?php

// app/Http/Controllers/Api/TicketController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CancelarTicketRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Conductor;
use App\Models\Ticket;
use App\Services\TicketService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de tickets digitales desde la app móvil del conductor.
 *
 * Endpoints disponibles:
 *  - GET  /tickets           → tickets vigentes del conductor.
 *  - POST /tickets           → comprar ticket (Art. 19, 22).
 *  - GET  /tickets/historial → historial paginado.
 *  - GET  /tickets/{ticket}  → detalle de un ticket.
 *  - POST /tickets/{ticket}/cancelar → cancelar antes de sesión.
 *
 * Los permisos se aplican en routes/api.php mediante middleware('permission:...').
 */
class TicketController extends ApiController
{
    public function __construct(private readonly TicketService $servicio)
    {
    }

    /**
     * Lista los tickets vigentes (pendiente, activo, en_tolerancia) del conductor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $conductor = Conductor::where('user_id', $request->user()->id)->first();

        if (! $conductor) {
            return $this->error('El usuario autenticado no es un conductor.', null, 403);
        }

        $tickets = Ticket::where('conductor_id', $conductor->id)
            ->whereIn('estado', ['pendiente_pago', 'pendiente', 'activo', 'en_tolerancia'])
            ->with(['vehiculo', 'zona', 'sesion'])
            ->orderByDesc('comprado_en')
            ->get();

        return $this->exito(TicketResource::collection($tickets), 'Tickets vigentes del conductor.');
    }

    /**
     * Compra un ticket digital para el conductor.
     *
     * @see Art. 19, 22 Ordenanza SIMETSA.
     *
     * @param  \App\Http\Requests\StoreTicketRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $conductor = Conductor::where('user_id', $request->user()->id)->first();

        if (! $conductor) {
            return $this->error('El usuario autenticado no es un conductor.', null, 403);
        }

        try {
            $ticket = $this->servicio->comprar(array_merge(
                $request->validated(),
                ['conductor_id' => $conductor->id]
            ));
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        $esDigital    = $ticket->proveedor->esDigital();
        $esEfectivoOtp = $ticket->proveedor === \App\Enums\ProveedorPago::Efectivo;
        $paymentUrl   = null;

        if ($esDigital) {
            $paymentUrl = $ticket->transacciones()->latest('id')->value('payment_url');
        }

        return $this->exito([
            'ticket'      => new TicketResource($ticket->load(['vehiculo', 'zona'])),
            'confirmado'  => ! $esDigital && ! $esEfectivoOtp,
            'payment_url' => $paymentUrl,
        ], 'Ticket comprado correctamente.', 201);
    }

    /**
     * Devuelve el historial paginado de tickets del conductor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function historial(Request $request): JsonResponse
    {
        $conductor = Conductor::where('user_id', $request->user()->id)->first();

        if (! $conductor) {
            return $this->error('El usuario autenticado no es un conductor.', null, 403);
        }

        $tickets = Ticket::where('conductor_id', $conductor->id)
            ->with(['vehiculo', 'zona', 'sesion', 'cancelacion'])
            ->orderByDesc('comprado_en')
            ->paginate(15);

        return $this->exito(
            TicketResource::collection($tickets),
            'Historial de tickets.',
        );
    }

    /**
     * Devuelve el detalle de un ticket específico del conductor.
     *
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        return $this->exito(
            new TicketResource($ticket->load(['vehiculo', 'zona', 'calle', 'sesion', 'cancelacion', 'comprobante', 'transacciones'])),
            'Detalle del ticket.',
        );
    }

    /**
     * Cancela voluntariamente un ticket antes de que se inicie la sesión.
     *
     * Solo el conductor propietario puede cancelar y solo en estado 'pendiente'.
     *
     * @param  \App\Http\Requests\CancelarTicketRequest  $request
     * @param  \App\Models\Ticket                        $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelar(CancelarTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('cancelar', $ticket);

        try {
            $this->servicio->cancelar($ticket, $request->user(), $request->validated()['motivo']);
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new TicketResource($ticket->fresh()->load(['vehiculo', 'zona', 'cancelacion'])),
            'Ticket cancelado correctamente.',
        );
    }
}
