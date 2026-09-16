<?php
// app/Http/Controllers/UsuarioController.php

namespace App\Http\Controllers;

use App\Enums\RolSistema;
use App\Http\Requests\UsuarioStoreRequest;
use App\Http\Requests\UsuarioUpdateRequest;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;


/**
 * Controlador del CRUD de usuarios del backoffice SIMETSA.
 *
 * Reglas:
 *  - Autorización vía permisos Spatie en constructor + UserPolicy en acciones con
 *    comprobaciones a nivel de modelo.
 *  - La lógica multi-tabla (User + PerfilUsuario + Spatie role) se delega al
 *    servicio UsuarioService dentro de DB::transaction.
 *  - destroy realiza una desactivación lógica (soft delete del perfil + activo=false).
 *  - Acción adicional reactivar restaura usuarios previamente desactivados.
 */
class UsuarioController extends Controller
{
    public function __construct(private UsuarioService $usuarioService)
    {
        $this->middleware('permission:usuarios.ver',      ['only' => ['index', 'show']]);
        $this->middleware('permission:usuarios.crear',    ['only' => ['create', 'store']]);
        $this->middleware('permission:usuarios.editar',   ['only' => ['edit', 'update', 'reactivar']]);
        $this->middleware('permission:usuarios.eliminar', ['only' => ['destroy']]);
    }

    /**
     * Devuelve TODOS los roles disponibles en el sistema (los 6 del Enum
     * + los custom creados desde RolController) como array [name => etiqueta]
     * apto para checkboxes de formulario.
     *
     * Los roles del Enum usan su etiqueta legible; los custom se humanizan
     * a partir de su nombre snake_case.
     *
     * @return array<string, string>
     */
    private function listadoRoles(): array
    {
        return Role::orderBy('name')
            ->get()
            ->mapWithKeys(function (Role $rol) {
                $enumCase = RolSistema::tryFrom($rol->name);
                $etiqueta = $enumCase
                    ? $enumCase->etiqueta()
                    : ucwords(str_replace('_', ' ', $rol->name));
                return [$rol->name => $etiqueta];
            })
            ->toArray();
    }



    /**
     * Lista los usuarios con filtros y paginación.
     *
     * Query params soportados:
     *  - buscar: texto en name, email o cedula.
     *  - rol:    filtrar por nombre del rol Spatie.
     *  - activo: '1' o '0' para filtrar por estado del perfil.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $query = User::with([
            'perfil' => fn ($q) => $q->withTrashed(), // Incluye inactivos
            'roles',
        ]);

        // Búsqueda libre en nombre, email y cédula
        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('name',  'ILIKE', "%{$buscar}%")
                  ->orWhere('email', 'ILIKE', "%{$buscar}%")
                  ->orWhereHas('perfil', function ($q2) use ($buscar) {
                      $q2->withTrashed()->where('cedula', 'LIKE', "%{$buscar}%");
                  });
            });
        }

        // Filtro por rol
        if ($rol = $request->input('rol')) {
            $query->role($rol);
        }

        // Filtro por estado (activo / inactivo)
        if ($request->filled('activo')) {
            $activo = (bool) $request->input('activo');
            $query->whereHas('perfil', fn ($q) => $q->withTrashed()->where('activo', $activo));
        }

        $usuarios = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'roles'    => RolSistema::listado(),
            'filtros'  => $request->only(['buscar', 'rol', 'activo']),
        ]);
    }

    /**
     * Formulario para crear un nuevo usuario.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('usuarios.create', [
            'roles'   => $this->listadoRoles(),
            'generos' => $this->listadoGeneros(),
        ]);
    }

    /**
     * Persiste el nuevo usuario.
     *
     * @param  \App\Http\Requests\UsuarioStoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(UsuarioStoreRequest $request): RedirectResponse
    {
        try {
            $usuario = $this->usuarioService->crear($request->validated());

            return redirect()
                ->route('usuarios.show', $usuario)
                ->with('success', "Usuario {$usuario->name} creado correctamente.");
        } catch (\DomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No se pudo crear el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el detalle de un usuario.
     *
     * @param  \App\Models\User  $usuario
     * @return \Illuminate\View\View
     */
    public function show(User $usuario): View
    {
        $usuario->load(['perfil' => fn ($q) => $q->withTrashed(), 'roles']);
        return view('usuarios.show', compact('usuario'));
    }

    /**
     * Formulario para editar un usuario existente.
     *
     * @param  \App\Models\User  $usuario
     * @return \Illuminate\View\View
     */
    public function edit(User $usuario): View
    {
        $usuario->load(['perfil' => fn ($q) => $q->withTrashed(), 'roles']);
        return view('usuarios.edit', [
            'usuario' => $usuario,
            'roles'   => $this->listadoRoles(),
            'generos' => $this->listadoGeneros(),
        ]);
    }

    /**
     * Persiste cambios en el usuario.
     *
     * @param  \App\Http\Requests\UsuarioUpdateRequest  $request
     * @param  \App\Models\User                          $usuario
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UsuarioUpdateRequest $request, User $usuario): RedirectResponse
    {
        try {
            $this->usuarioService->actualizar($usuario, $request->validated());

            return redirect()
                ->route('usuarios.show', $usuario)
                ->with('success', "Usuario {$usuario->name} actualizado correctamente.");
        } catch (\DomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No se pudo actualizar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Desactiva el usuario (soft delete del perfil + activo=false).
     * NO elimina físicamente al usuario para preservar integridad de datos.
     *
     * @param  \App\Models\User  $usuario
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $usuario): RedirectResponse
    {
        try {
            $this->usuarioService->desactivar($usuario);

            return redirect()
                ->route('usuarios.index')
                ->with('success', "Usuario {$usuario->name} desactivado correctamente.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', "No se pudo desactivar el usuario: {$e->getMessage()}");
        }
    }

    /**
     * Reactiva un usuario previamente desactivado.
     *
     * @param  \App\Models\User  $usuario
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reactivar(User $usuario): RedirectResponse
    {
        try {
            $this->usuarioService->reactivar($usuario);

            return redirect()
                ->route('usuarios.show', $usuario)
                ->with('success', "Usuario {$usuario->name} reactivado correctamente.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', "No se pudo reactivar el usuario: {$e->getMessage()}");
        }
    }

    /**
     * Listado de opciones de género para los selects.
     *
     * @return array<string, string>
     */
    private function listadoGeneros(): array
    {
        return [
            'M'  => 'Masculino',
            'F'  => 'Femenino',
            'O'  => 'Otro',
            'ND' => 'No declara',
        ];
    }
}