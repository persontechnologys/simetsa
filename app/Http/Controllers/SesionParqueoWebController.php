<?php

// app/Http/Controllers/SesionParqueoWebController.php

namespace App\Http\Controllers;

use App\Enums\EstadoSesionParqueo;
use App\Models\AgenteParqueo;
use App\Models\SesionParqueo;
use App\Models\Zona;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Backoffice de supervisión de sesiones de parqueo.
 *
 * Solo lectura: index. Las sesiones se crean desde la API del agente
 * al validar la placa en calle (Art. 16 Ordenanza SIMETSA).
 *
 * Permiso requerido: sesiones_parqueo.ver.
 */
class SesionParqueoWebController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:sesiones_parqueo.ver', ['only' => ['index']]);
    }

    /**
     * Lista sesiones con filtros: agente, zona, estado, fecha.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = SesionParqueo::with(['ticket', 'agente.user', 'plaza'])
            ->when($request->agente_parqueo_id, fn ($q, $a) =>
                $q->where('agente_id', $a)
            )
            ->when($request->zona_id, fn ($q, $z) =>
                $q->whereHas('ticket', fn ($qt) =>
                    $qt->where('zona_id', $z)
                )
            )
            ->when($request->estado, fn ($q, $e) =>
                $q->where('estado', $e)
            )
            ->when($request->fecha_desde, fn ($q, $f) =>
                $q->whereDate('inicio_at', '>=', $f)
            )
            ->when($request->fecha_hasta, fn ($q, $f) =>
                $q->whereDate('inicio_at', '<=', $f)
            )
            ->orderByDesc('inicio_at');

        return view('sesiones-parqueo.index', [
            'sesiones' => $query->paginate(25)->withQueryString(),
            'agentes'  => AgenteParqueo::where('estado', AgenteParqueo::ESTADO_ACTIVO)
                ->with('user')
                ->orderBy('codigo')
                ->get(),
            'zonas'    => Zona::where('activo', true)->orderBy('nombre')->get(),
            'estados'  => collect(EstadoSesionParqueo::cases())->mapWithKeys(
                fn ($e) => [$e->value => $e->etiqueta()]
            )->all(),
        ]);
    }
}
