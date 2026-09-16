<?php
// app/Http/Controllers/RolController.php

namespace App\Http\Controllers;

use App\Enums\RolSistema;
use App\Http\Requests\RolStoreRequest;
use App\Http\Requests\RolUpdateRequest;
use App\Services\RolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Controlador del CRUD de roles y asignación de permisos.
 *
 * Reglas:
 *  - Autorización vía permisos Spatie en constructor para acciones CRUD básicas.
 *  - Acciones destructivas (edit, update, destroy) invocan RolPolicy inline
 *    para proteger al rol super_admin y roles del sistema.
 *  - Los 6 roles del Enum RolSistema no se pueden renombrar ni eliminar;
 *    sí editan sus permisos (excepto super_admin).
 *  - Eliminación bloqueada si el rol tiene usuarios asignados.
 */
class RolController extends Controller
{
    /**
     * @param  \App\Services\RolService  $rolService
     */
    public function __construct(private RolService $rolService)
    {
        $this->middleware('permission:roles.ver',      ['only' => ['index', 'show']]);
        $this->middleware('permission:roles.crear',    ['only' => ['create', 'store']]);
        $this->middleware('permission:roles.editar',   ['only' => ['edit', 'update']]);
        $this->middleware('permission:roles.eliminar', ['only' => ['destroy']]);
    }

    /**
     * Lista los roles con contadores de usuarios y permisos.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $roles = Role::withCount(['permissions', 'users'])
            ->orderBy('name')
            ->paginate(20);

        return view('roles.index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Formulario de creación de un rol custom.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('roles.create', [
            'catalogoPermisos'     => config('simetsa_permisos'),
            'permisosSeleccionados'=> [],
            'totalesPorModulo'     => $this->totalesPorModulo(),
            'esRolDelSistema'      => false,
            'deshabilitarPermisos' => false,
        ]);
    }

    /**
     * Persiste el rol nuevo.
     *
     * @param  \App\Http\Requests\RolStoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(RolStoreRequest $request): RedirectResponse
    {
        $rol = $this->rolService->crear($request->validated());

        return redirect()
            ->route('roles.show', $rol)
            ->with('success', "Rol '{$rol->name}' creado correctamente.");
    }

    /**
     * Detalle de un rol con sus permisos y usuarios asignados.
     *
     * @param  \Spatie\Permission\Models\Role  $rol
     * @return \Illuminate\View\View
     */
    public function show(Role $rol): View
    {
        $rol->load(['permissions', 'users' => fn ($q) => $q->limit(50)]);

        return view('roles.show', [
            'rol'              => $rol,
            'catalogoPermisos' => config('simetsa_permisos'),
            'esRolDelSistema'  => $this->esRolDelSistema($rol->name),
        ]);
    }

    /**
     * Formulario de edición del rol.
     *
     * @param  \Spatie\Permission\Models\Role  $rol
     * @return \Illuminate\View\View
     */
    public function edit(Role $rol): View
    {
        // RolPolicy::update() bloquea edición del rol super_admin para todos los roles.
        $this->authorize('update', $rol);

        $rol->load('permissions');

        $esRolDelSistema  = $this->esRolDelSistema($rol->name);
        $esSuperAdmin     = $rol->name === RolSistema::SuperAdmin->value;

        return view('roles.edit', [
            'rol'                  => $rol,
            'catalogoPermisos'     => config('simetsa_permisos'),
            'permisosSeleccionados'=> $rol->permissions->pluck('name')->toArray(),
            'totalesPorModulo'     => $this->totalesPorModulo(),
            'esRolDelSistema'      => $esRolDelSistema,
            // super_admin tiene todos los permisos siempre — UI bloqueada
            'deshabilitarPermisos' => $esSuperAdmin,
        ]);
    }

    /**
     * Persiste cambios del rol (nombre y/o permisos).
     *
     * @param  \App\Http\Requests\RolUpdateRequest  $request
     * @param  \Spatie\Permission\Models\Role       $rol
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(RolUpdateRequest $request, Role $rol): RedirectResponse
    {
        // RolPolicy::update() bloquea actualización del rol super_admin.
        $this->authorize('update', $rol);

        $datos = $request->validated();

        // Si es rol del sistema, preserva el nombre original
        if ($this->esRolDelSistema($rol->name)) {
            unset($datos['name']);
        }

        // Si es super_admin, ignora cualquier intento de cambiar permisos
        if ($rol->name === RolSistema::SuperAdmin->value) {
            // syncPermissions con todos los permisos para mantener el bypass
            $datos['permisos'] = Permission::pluck('name')->toArray();
        }

        $this->rolService->actualizar($rol, $datos);

        return redirect()
            ->route('roles.show', $rol)
            ->with('success', "Rol '{$rol->name}' actualizado correctamente.");
    }

    /**
     * Elimina el rol previa validación redundante (defense in depth).
     * La policy ya bloquea estos casos, pero los duplicamos aquí para que
     * un bug futuro en el policy no permita destruir el sistema.
     *
     * @param  \Spatie\Permission\Models\Role  $rol
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Role $rol): RedirectResponse
    {
        // RolPolicy::delete() bloquea roles del sistema, super_admin y roles con usuarios.
        $this->authorize('delete', $rol);

        // Defense-in-depth: garantías adicionales independientes de la policy.
        if ($this->esRolDelSistema($rol->name)) {
            abort(403, 'Los roles del sistema no pueden eliminarse desde la interfaz.');
        }

        if ($rol->users()->count() > 0) {
            return redirect()
                ->route('roles.index')
                ->with('error', "El rol '{$rol->name}' tiene usuarios asignados y no puede eliminarse.");
        }

        $nombre = $rol->name;
        $this->rolService->eliminar($rol);

        return redirect()
            ->route('roles.index')
            ->with('success', "Rol '{$nombre}' eliminado correctamente.");
    }

    /**
     * Calcula la cantidad total de permisos por cada módulo del catálogo.
     * Útil para mostrar contadores "X / Total" en la UI.
     *
     * @return array<string, int>
     */
    private function totalesPorModulo(): array
    {
        $totales = [];
        foreach (config('simetsa_permisos') as $modulo => $entidades) {
            $totales[$modulo] = 0;
            foreach ($entidades as $acciones) {
                $totales[$modulo] += count($acciones);
            }
        }
        return $totales;
    }

    /**
     * @param  string  $nombre
     * @return bool
     */
    private function esRolDelSistema(string $nombre): bool
    {
        return in_array(
            $nombre,
            array_column(RolSistema::cases(), 'value'),
            true
        );
    }
}