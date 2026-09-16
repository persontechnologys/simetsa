<?php
// app/Http/Controllers/CredencialDiscapacidadController.php

namespace App\Http\Controllers;

use App\Http\Requests\AprobacionCredencialRequest;
use App\Models\CredencialDiscapacidad;
use App\Services\CredencialDiscapacidadService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Backoffice: listado y aprobación de credenciales CONADIS (Art. 26 Ordenanza SIMETSA).
 *
 * El comisario o director puede ver todas las credenciales, filtrar por estado
 * y aprobar/rechazar las que están pendientes.
 */
class CredencialDiscapacidadController extends Controller
{
    public function __construct(private readonly CredencialDiscapacidadService $servicio)
    {
        $this->middleware('permission:credenciales_discapacidad.ver')->only('index');
        $this->middleware('permission:credenciales_discapacidad.aprobar')->only(['aprobar', 'rechazar']);
    }

    /**
     * Lista todas las credenciales CONADIS con filtro opcional por estado (Art. 26).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $estados = [
            CredencialDiscapacidad::ESTADO_PENDIENTE,
            CredencialDiscapacidad::ESTADO_APROBADA,
            CredencialDiscapacidad::ESTADO_RECHAZADA,
            CredencialDiscapacidad::ESTADO_VENCIDA,
        ];

        $query = CredencialDiscapacidad::with(['conductor.user', 'aprobadaPorUsuario'])
            ->orderByRaw("CASE WHEN estado = 'pendiente' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        if ($request->filled('estado') && in_array($request->estado, $estados)) {
            $query->where('estado', $request->estado);
        }

        $credenciales = $query->paginate(20)->withQueryString();

        return view('credenciales-discapacidad.index', compact('credenciales', 'estados'));
    }

    /**
     * Aprueba una credencial CONADIS en estado pendiente.
     *
     * @see Art. 26 Ordenanza SIMETSA.
     *
     * @param  \App\Http\Requests\AprobacionCredencialRequest  $request
     * @param  \App\Models\CredencialDiscapacidad               $credencial_discapacidad
     * @return \Illuminate\Http\RedirectResponse
     */
    public function aprobar(AprobacionCredencialRequest $request, CredencialDiscapacidad $credencial_discapacidad): RedirectResponse
    {
        try {
            $this->servicio->aprobar($credencial_discapacidad, $request->user(), $request->validated());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Credencial CONADIS aprobada correctamente.');
    }

    /**
     * Rechaza una credencial CONADIS en estado pendiente.
     *
     * @see Art. 26 Ordenanza SIMETSA.
     *
     * @param  \App\Http\Requests\AprobacionCredencialRequest  $request
     * @param  \App\Models\CredencialDiscapacidad               $credencial_discapacidad
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rechazar(AprobacionCredencialRequest $request, CredencialDiscapacidad $credencial_discapacidad): RedirectResponse
    {
        try {
            $this->servicio->rechazar($credencial_discapacidad, $request->validated());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Credencial CONADIS rechazada.');
    }
}
