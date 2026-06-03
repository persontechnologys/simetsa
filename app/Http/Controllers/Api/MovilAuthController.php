<?php
// app/Http/Controllers/Api/MovilAuthController.php

namespace App\Http\Controllers\Api;

use App\Enums\RolSistema;
use App\Http\Requests\LoginMovilRequest;
use App\Http\Resources\AgenteParqueoResource;
use App\Http\Resources\ConductorResource;
use App\Http\Resources\UsuarioMovilResource;
use App\Models\AgenteParqueo;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Autenticación móvil unificada — cualquier rol habilitado en config/simetsa.php (Fase 9 refactor).
 *
 * Reemplaza el patrón duplicado de AuthController (solo conductor) +
 * AgenteAuthController (solo agente). Agregar un nuevo rol móvil solo
 * requiere: (1) añadirlo en config('simetsa.roles_movil') y (2) agregar
 * un case en resolverPerfilOperativo() si tiene un modelo específico.
 *
 * Los endpoints legacy (/api/v1/login, /api/v1/agente/auth/login) permanecen
 * intactos para backward compatibility con tests existentes.
 */
class MovilAuthController extends ApiController
{
    /**
     * Inicia sesión unificada para cualquier rol habilitado en la app móvil.
     *
     * Verifica credenciales, rol permitido, estado activo del perfil operativo
     * y emite un token Sanctum con nombre 'movil-v2'.
     *
     * @param  \App\Http\Requests\LoginMovilRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginMovilRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $user  = User::where('email', $datos['email'])->first();

        if (! $user || ! Hash::check($datos['password'], $user->password)) {
            return $this->error('Credenciales incorrectas.', null, 401);
        }

        // Verificar que el usuario tenga al menos un rol habilitado para la app móvil
        $rolesMovil     = config('simetsa.roles_movil', []);
        $rolesUsuario   = $user->getRoleNames()->values()->all();
        $rolesPermitidos = array_values(array_intersect($rolesUsuario, $rolesMovil));

        if (empty($rolesPermitidos)) {
            return $this->error('Esta cuenta no tiene acceso a la app móvil.', null, 403);
        }

        // Primer rol permitido determina el perfil operativo (en SIMETSA los usuarios tienen un solo rol)
        $rolPrimario = $rolesPermitidos[0];

        $perfilOperativo = $this->resolverPerfilOperativo($user, $rolPrimario);
        if ($perfilOperativo instanceof JsonResponse) {
            return $perfilOperativo; // propaga el 403 con mensaje específico
        }

        // Cargar relaciones para UsuarioMovilResource (roles + permissions via Spatie)
        $user->loadMissing('roles', 'permissions');

        $token = $user->createToken('movil-v2')->plainTextToken;

        return $this->exito([
            'token'            => $token,
            'tipo_token'       => 'Bearer',
            'usuario'          => new UsuarioMovilResource($user),
            'perfil_operativo' => $perfilOperativo,
        ], 'Sesión iniciada.');
    }

    /**
     * Cierra la sesión revocando el token activo.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $plain = $request->bearerToken();
        if ($plain) {
            $token = PersonalAccessToken::findToken($plain);
            if ($token) {
                $token->delete();
            }
        }

        try {
            $request->user()->tokens()->delete();
        } catch (\Throwable $e) {
            Log::warning('Error al revocar tokens en logout movil', ['error' => $e->getMessage()]);
        }

        return $this->exito(null, 'Sesión cerrada correctamente.');
    }

    /**
     * Devuelve la sesión completa del usuario autenticado (usuario + perfil operativo).
     *
     * Útil para refrescar la sesión en la app (ej. después del registro de conductor).
     * No devuelve el token — el caller ya lo tiene.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $plain = $request->bearerToken();
        if (! $plain || ! PersonalAccessToken::findToken($plain)) {
            return $this->error('Token inválido o expirado.', null, 401);
        }

        $user = $request->user();
        $user->loadMissing('roles', 'permissions');

        $rolesMovil     = config('simetsa.roles_movil', []);
        $rolesPermitidos = array_values(
            array_intersect($user->getRoleNames()->values()->all(), $rolesMovil)
        );

        if (empty($rolesPermitidos)) {
            return $this->error('Esta cuenta no tiene acceso a la app móvil.', null, 403);
        }

        $rolPrimario = $rolesPermitidos[0];
        $perfilOperativo = $this->resolverPerfilOperativo($user, $rolPrimario);
        if ($perfilOperativo instanceof JsonResponse) {
            return $perfilOperativo;
        }

        return $this->exito([
            'usuario'          => new UsuarioMovilResource($user),
            'perfil_operativo' => $perfilOperativo,
        ], 'Sesión activa.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resuelve el perfil operativo según el rol del usuario.
     *
     * Devuelve un JsonResource (serializable por response()->json()) o una
     * JsonResponse de error (403) si el perfil no está activo.
     * Para roles sin perfil operativo propio devuelve null.
     *
     * @param  \App\Models\User  $user
     * @param  string            $rol  — valor de RolSistema
     * @return \Illuminate\Http\Resources\Json\JsonResource|null|\Illuminate\Http\JsonResponse
     */
    private function resolverPerfilOperativo(User $user, string $rol): mixed
    {
        return match ($rol) {
            RolSistema::Conductor->value     => $this->perfilConductor($user),
            RolSistema::AgenteParqueo->value => $this->perfilAgente($user),
            // Roles futuros: agregar cases aquí
            default => null, // rol habilitado pero sin perfil operativo específico
        };
    }

    /**
     * @return \App\Http\Resources\ConductorResource|\Illuminate\Http\JsonResponse
     */
    private function perfilConductor(User $user): mixed
    {
        $conductor = Conductor::where('user_id', $user->id)->first();

        if (! $conductor) {
            return $this->error('Conductor no encontrado.', null, 403);
        }

        if ($conductor->estado !== Conductor::ESTADO_ACTIVO) {
            return $this->error('El conductor no está activo en el sistema.', null, 403);
        }

        return new ConductorResource($conductor->load('user.perfil'));
    }

    /**
     * @return \App\Http\Resources\AgenteParqueoResource|\Illuminate\Http\JsonResponse
     */
    private function perfilAgente(User $user): mixed
    {
        $agente = AgenteParqueo::where('user_id', $user->id)
            ->with(['user', 'asignaciones.zona'])
            ->first();

        if (! $agente) {
            return $this->error('Agente de parqueo no encontrado.', null, 403);
        }

        if ($agente->estado !== AgenteParqueo::ESTADO_ACTIVO) {
            return $this->error('El agente no se encuentra activo en el sistema.', null, 403);
        }

        return new AgenteParqueoResource($agente);
    }
}
