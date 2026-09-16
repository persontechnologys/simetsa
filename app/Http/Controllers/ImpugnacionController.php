<?php

// app/Http/Controllers/ImpugnacionController.php

namespace App\Http\Controllers;

use App\Models\AgenteParqueo;
use App\Models\Impugnacion;
use App\Services\ImpugnacionService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gestión de impugnaciones de infracciones en el backoffice.
 *
 * El comisario puede ver, admitir, rechazar y resolver impugnaciones (Art. 17.f).
 */
class ImpugnacionController extends Controller
{
    public function __construct(private readonly ImpugnacionService $servicio)
    {
        $this->middleware('permission:impugnaciones.ver',     ['only' => ['index', 'show']]);
        $this->middleware('permission:impugnaciones.resolver', ['only' => ['admitir', 'rechazar', 'resolver']]);
    }

    /**
     * Listado de impugnaciones con filtros: estado, agente, fecha.
     */
    public function index(Request $request): View
    {
        $agentes = AgenteParqueo::orderBy('codigo')->get(['id', 'codigo']);

        $impugnaciones = Impugnacion::query()
            ->with(['infraccion.agente', 'conductor.user.perfil'])
            ->when($request->estado, fn ($q) => $q->where('estado', $request->estado))
            ->when($request->agente_id, fn ($q) => $q->whereHas(
                'infraccion',
                fn ($qi) => $qi->where('agente_parqueo_id', $request->agente_id)
            ))
            ->when($request->fecha_desde, fn ($q) => $q->whereDate('created_at', '>=', $request->fecha_desde))
            ->when($request->fecha_hasta, fn ($q) => $q->whereDate('created_at', '<=', $request->fecha_hasta))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('impugnaciones.index', [
            'impugnaciones' => $impugnaciones,
            'agentes'       => $agentes,
            'estados'       => Impugnacion::estados(),
        ]);
    }

    /**
     * Detalle de la impugnación con acciones disponibles.
     */
    public function show(Impugnacion $impugnacion): View
    {
        $impugnacion->load(['infraccion.zona', 'infraccion.agente', 'conductor.user.perfil', 'resolutor']);

        return view('impugnaciones.show', compact('impugnacion'));
    }

    /**
     * El comisario admite la impugnación para análisis formal.
     */
    public function admitir(Request $request, Impugnacion $impugnacion): RedirectResponse
    {
        try {
            $this->servicio->admitir($impugnacion, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('impugnaciones.show', $impugnacion)
            ->with('success', 'Impugnación admitida para análisis.');
    }

    /**
     * El comisario rechaza la impugnación con resolución motivada.
     */
    public function rechazar(Request $request, Impugnacion $impugnacion): RedirectResponse
    {
        $request->validate([
            'resolucion' => ['required', 'string', 'min:10'],
        ]);

        try {
            $this->servicio->rechazar($impugnacion, $request->user(), $request->input('resolucion'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('impugnaciones.show', $impugnacion)
            ->with('success', 'Impugnación rechazada.');
    }

    /**
     * El comisario resuelve la impugnación a favor del conductor.
     */
    public function resolver(Request $request, Impugnacion $impugnacion): RedirectResponse
    {
        $request->validate([
            'resolucion' => ['required', 'string', 'min:10'],
        ]);

        try {
            $this->servicio->resolver($impugnacion, $request->user(), $request->input('resolucion'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('impugnaciones.show', $impugnacion)
            ->with('success', 'Impugnación resuelta correctamente.');
    }
}
