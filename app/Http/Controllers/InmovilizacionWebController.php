<?php

// app/Http/Controllers/InmovilizacionWebController.php

namespace App\Http\Controllers;

use App\Enums\EstadoInmovilizacion;
use App\Http\Requests\LiberarInmovilizacionRequest;
use App\Models\AgenteParqueo;
use App\Models\Inmovilizacion;
use App\Services\InfraccionService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Backoffice de supervisión y liberación administrativa de inmovilizaciones.
 *
 * Acceso:
 *  - index + show: permiso inmovilizaciones.ver.
 *  - liberar: permiso inmovilizaciones.retirar (Art. 15 Ordenanza SIMETSA).
 */
class InmovilizacionWebController extends Controller
{
    public function __construct(private readonly InfraccionService $servicio)
    {
        $this->middleware('permission:inmovilizaciones.ver',     ['only' => ['index', 'show']]);
        $this->middleware('permission:inmovilizaciones.retirar', ['only' => ['liberar']]);
    }

    /**
     * Lista inmovilizaciones con filtros: agente, estado, placa, fecha.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = Inmovilizacion::with(['infraccion', 'agente.user'])
            ->when($request->estado, fn ($q, $e) =>
                $q->where('estado', $e)
            )
            ->when($request->agente_parqueo_id, fn ($q, $a) =>
                $q->where('agente_parqueo_id', $a)
            )
            ->when($request->placa, fn ($q, $p) =>
                $q->whereHas('infraccion', fn ($qi) =>
                    $qi->where('placa', strtoupper(trim($p)))
                )
            )
            ->when($request->fecha_desde, fn ($q, $f) =>
                $q->whereDate('inmovilizada_en', '>=', $f)
            )
            ->when($request->fecha_hasta, fn ($q, $f) =>
                $q->whereDate('inmovilizada_en', '<=', $f)
            )
            ->orderByDesc('inmovilizada_en');

        return view('inmovilizaciones.index', [
            'inmovilizaciones' => $query->paginate(25)->withQueryString(),
            'agentes'          => AgenteParqueo::where('estado', AgenteParqueo::ESTADO_ACTIVO)
                ->with('user')
                ->orderBy('codigo')
                ->get(),
            'estados'          => collect(EstadoInmovilizacion::cases())->mapWithKeys(
                fn ($e) => [$e->value => $e->etiqueta()]
            )->all(),
        ]);
    }

    /**
     * Detalle de una inmovilización con infracción asociada.
     *
     * @param  \App\Models\Inmovilizacion  $inmovilizacion
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Inmovilizacion $inmovilizacion): View
    {
        $inmovilizacion->load([
            'infraccion.zona',
            'infraccion.calle',
            'infraccion.transacciones',
            'agente.user.perfil',
            'anuladaPor.perfil',
        ]);

        return view('inmovilizaciones.show', compact('inmovilizacion'));
    }

    /**
     * Libera administrativamente un vehículo inmovilizado (Art. 15 Ordenanza SIMETSA).
     *
     * El comisario puede liberar el candado indicando el motivo administrativo,
     * independientemente del estado de pago de la infracción.
     *
     * @param  \App\Http\Requests\LiberarInmovilizacionRequest  $request
     * @param  \App\Models\Inmovilizacion                       $inmovilizacion
     * @return \Illuminate\Http\RedirectResponse
     */
    public function liberar(LiberarInmovilizacionRequest $request, Inmovilizacion $inmovilizacion): RedirectResponse
    {
        try {
            $this->servicio->liberar($inmovilizacion, $request->validated()['motivo']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inmovilizaciones.show', $inmovilizacion)
            ->with('success', "Inmovilización #{$inmovilizacion->id} liberada administrativamente.");
    }
}
