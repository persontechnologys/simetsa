<?php

// app/Http/Controllers/TransaccionPagoWebController.php

namespace App\Http\Controllers;

use App\Enums\EstadoTransaccion;
use App\Enums\ProveedorPago;
use App\Models\TransaccionPago;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Backoffice de supervisión de transacciones de pago (Art. 21 Ordenanza SIMETSA).
 *
 * Solo lectura: index. Permite al comisario auditar pagos registrados
 * con cualquier proveedor (Deuna, PagoSimulado, etc.).
 *
 * Permiso requerido: pagos.ver.
 */
class TransaccionPagoWebController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pagos.ver', ['only' => ['index']]);
    }

    /**
     * Lista transacciones con filtros: estado, proveedor, concepto, fecha.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = TransaccionPago::with('concepto')
            ->when($request->estado, fn ($q, $e) =>
                $q->where('estado', $e)
            )
            ->when($request->proveedor, fn ($q, $p) =>
                $q->where('proveedor', $p)
            )
            ->when($request->external_reference, fn ($q, $r) =>
                $q->where('external_reference', 'LIKE', "%{$r}%")
            )
            ->when($request->fecha_desde, fn ($q, $f) =>
                $q->whereDate('created_at', '>=', $f)
            )
            ->when($request->fecha_hasta, fn ($q, $f) =>
                $q->whereDate('created_at', '<=', $f)
            )
            ->orderByDesc('created_at');

        return view('transacciones.index', [
            'transacciones' => $query->paginate(25)->withQueryString(),
            'estados'       => collect(EstadoTransaccion::cases())->mapWithKeys(
                fn ($e) => [$e->value => $e->etiqueta()]
            )->all(),
            'proveedores'   => collect(ProveedorPago::cases())->mapWithKeys(
                fn ($p) => [$p->value => $p->value]
            )->all(),
        ]);
    }
}
