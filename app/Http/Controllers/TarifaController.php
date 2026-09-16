<?php
// app/Http/Controllers/TarifaController.php

namespace App\Http\Controllers;

use App\Http\Requests\TarifaStoreRequest;
use App\Http\Requests\TarifaUpdateRequest;
use App\Models\Tarifa;
use App\Models\TipoPlaza;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador CRUD de Tarifas.
 *
 * Vista index agrupa las tarifas por TipoPlaza para mostrar fácilmente
 * la tarifa vigente vs el historial de cada tipo.
 */
class TarifaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tarifas.ver')->only('index');
        $this->middleware('permission:tarifas.crear')->only(['create', 'store']);
        $this->middleware('permission:tarifas.editar')->only(['edit', 'update']);
        $this->middleware('permission:tarifas.eliminar')->only('destroy');
    }

    public function index(): View
    {
        // Eager load: cada TipoPlaza con sus tarifas (incluidas soft-deleted) ordenadas desc por vigencia
        $tiposPlaza = TipoPlaza::activos()
            ->with(['tarifas' => fn ($q) => $q->withTrashed()->orderBy('vigente_desde', 'desc')])
            ->orderBy('nombre')
            ->get();

        return view('tarifas.index', compact('tiposPlaza'));
    }

    public function create(): View
    {
        return view('tarifas.create', [
            'tarifa'     => null,
            'tiposPlaza' => TipoPlaza::activos()->orderBy('nombre')->get(),
        ]);
    }

    public function store(TarifaStoreRequest $request): RedirectResponse
    {
        $tarifa = Tarifa::create($request->validated());
        return redirect()
            ->route('tarifas.index')
            ->with('success', "Tarifa '{$tarifa->nombre}' creada correctamente.");
    }

    public function edit(Tarifa $tarifa): View
    {
        return view('tarifas.edit', [
            'tarifa'     => $tarifa,
            'tiposPlaza' => TipoPlaza::activos()->orderBy('nombre')->get(),
        ]);
    }

    public function update(TarifaUpdateRequest $request, Tarifa $tarifa): RedirectResponse
    {
        $tarifa->update($request->validated());
        return redirect()
            ->route('tarifas.index')
            ->with('success', "Tarifa '{$tarifa->nombre}' actualizada correctamente.");
    }

    public function destroy(Tarifa $tarifa): RedirectResponse
    {
        $tarifa->delete(); // soft delete
        return redirect()
            ->route('tarifas.index')
            ->with('success', "Tarifa '{$tarifa->nombre}' eliminada (soft delete).");
    }

    public function reactivar(Tarifa $tarifa): RedirectResponse
    {
        $tarifa->restore();
        return redirect()
            ->route('tarifas.index')
            ->with('success', "Tarifa '{$tarifa->nombre}' restaurada correctamente.");
    }
}