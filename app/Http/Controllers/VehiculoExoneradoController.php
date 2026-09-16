<?php
// app/Http/Controllers/VehiculoExoneradoController.php

namespace App\Http\Controllers;

use App\Http\Requests\VehiculoExoneradoStoreRequest;
use App\Http\Requests\VehiculoExoneradoUpdateRequest;
use App\Models\VehiculoExonerado;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * CRUD de vehículos exonerados del pago de parqueo (Art. 27 Ordenanza SIMETSA).
 *
 * Exclusivo del backoffice: Policía, Bomberos, FF.AA., autoridades del Estado
 * y vehículos municipales tienen exoneración de hasta 2 horas.
 */
class VehiculoExoneradoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:vehiculos_exonerados.ver')->only('index');
        $this->middleware('permission:vehiculos_exonerados.crear')->only(['create', 'store']);
        $this->middleware('permission:vehiculos_exonerados.editar')->only(['edit', 'update', 'toggle']);
        $this->middleware('permission:vehiculos_exonerados.eliminar')->only('destroy');
    }

    /**
     * Lista de vehículos exonerados.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        $vehiculos = VehiculoExonerado::with('registradoPor')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('vehiculos-exonerados.index', [
            'vehiculos' => $vehiculos,
            'tipos'     => VehiculoExonerado::listadoTipos(),
        ]);
    }

    /**
     * Formulario de registro.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        return view('vehiculos-exonerados.create', [
            'tipos' => VehiculoExonerado::listadoTipos(),
        ]);
    }

    /**
     * Registra un nuevo vehículo exonerado.
     *
     * @see Art. 27 Ordenanza SIMETSA.
     *
     * @param  \App\Http\Requests\VehiculoExoneradoStoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(VehiculoExoneradoStoreRequest $request): RedirectResponse
    {
        VehiculoExonerado::create(array_merge($request->validated(), [
            'registrado_por'      => $request->user()->id,
            'activo'              => (bool) ($request->validated()['activo'] ?? true),
            'tiempo_maximo_horas' => $request->validated()['tiempo_maximo_horas'] ?? 2,
        ]));

        return redirect()->route('vehiculos-exonerados.index')
            ->with('success', 'Vehículo exonerado registrado correctamente.');
    }

    /**
     * Formulario de edición.
     *
     * @param  \App\Models\VehiculoExonerado  $vehiculo_exonerado
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(VehiculoExonerado $vehiculo_exonerado): View
    {
        return view('vehiculos-exonerados.edit', [
            'vehiculo' => $vehiculo_exonerado,
            'tipos'    => VehiculoExonerado::listadoTipos(),
        ]);
    }

    /**
     * Actualiza los datos del vehículo exonerado.
     *
     * @param  \App\Http\Requests\VehiculoExoneradoUpdateRequest  $request
     * @param  \App\Models\VehiculoExonerado                      $vehiculo_exonerado
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(VehiculoExoneradoUpdateRequest $request, VehiculoExonerado $vehiculo_exonerado): RedirectResponse
    {
        $vehiculo_exonerado->update(array_merge($request->validated(), [
            'activo' => (bool) ($request->validated()['activo'] ?? false),
        ]));

        return redirect()->route('vehiculos-exonerados.index')
            ->with('success', 'Vehículo exonerado actualizado correctamente.');
    }

    /**
     * Activa o suspende temporalmente una exoneración sin eliminarla (Art. 27).
     *
     * Permite al comisario suspender una exoneración de forma reversible cuando
     * el vehículo deje de cumplir los requisitos temporalmente.
     *
     * @param  \App\Models\VehiculoExonerado  $vehiculo_exonerado
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggle(VehiculoExonerado $vehiculo_exonerado): RedirectResponse
    {
        $vehiculo_exonerado->update(['activo' => ! $vehiculo_exonerado->activo]);
        $estado = $vehiculo_exonerado->activo ? 'activada' : 'suspendida';

        return back()->with('success', "Exoneración de la placa {$vehiculo_exonerado->placa} {$estado}.");
    }

    /**
     * Elimina (soft delete) el registro de exoneración.
     *
     * @param  \App\Models\VehiculoExonerado  $vehiculo_exonerado
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(VehiculoExonerado $vehiculo_exonerado): RedirectResponse
    {
        $vehiculo_exonerado->delete();

        return redirect()->route('vehiculos-exonerados.index')
            ->with('success', 'Registro de exoneración eliminado correctamente.');
    }
}
