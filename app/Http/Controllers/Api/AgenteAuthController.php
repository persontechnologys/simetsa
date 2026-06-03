<?php
// app/Http/Controllers/Api/AgenteAuthController.php

namespace App\Http\Controllers\Api;

use App\Enums\RolSistema;
use App\Http\Requests\LoginConductorRequest;
use App\Http\Resources\AgenteParqueoResource;
use App\Models\AgenteParqueo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Autenticación de agentes de parqueo para la app móvil (Fase 9.B).
 *
 * Los agentes no se registran desde la app: son activados por el comisario
 * tras completar el proceso de selección y capacitación (Arts. 32-36).
 */
class AgenteAuthController extends ApiController
{
    /**
     * Inicia sesión de un agente de parqueo y emite un token personal Sanctum.
     *
     * Verifica credenciales, rol 'agente_parqueo' y estado activo del agente.
     *
     * @param  \App\Http\Requests\LoginConductorRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginConductorRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $user  = User::where('email', $datos['email'])->first();

        if (! $user || ! Hash::check($datos['password'], $user->password)) {
            return $this->error('Credenciales incorrectas.', null, 401);
        }

        if (! $user->hasRole(RolSistema::AgenteParqueo->value)) {
            return $this->error('Esta cuenta no corresponde a un agente de parqueo.', null, 403);
        }

        $agente = AgenteParqueo::where('user_id', $user->id)->first();

        if (! $agente) {
            return $this->error('Agente no encontrado.', null, 403);
        }

        if ($agente->estado !== AgenteParqueo::ESTADO_ACTIVO) {
            return $this->error('El agente no se encuentra activo en el sistema.', null, 403);
        }

        $token = $user->createToken('movil-agente')->plainTextToken;

        return $this->exito([
            'token'      => $token,
            'tipo_token' => 'Bearer',
            'rol'        => RolSistema::AgenteParqueo->value,
            // Cargamos asignaciones.zona para exponer zona_actual en el resource (Fase 9.E)
            'agente'     => new AgenteParqueoResource($agente->load(['user', 'asignaciones.zona'])),
        ], 'Sesión iniciada.');
    }

    /**
     * Cierra la sesión del agente revocando el token activo.
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
            Log::warning('Error al revocar tokens del agente en logout', ['error' => $e->getMessage()]);
        }

        return $this->exito(null, 'Sesión cerrada correctamente.');
    }

    /**
     * Devuelve los datos del agente autenticado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function perfil(Request $request): JsonResponse
    {
        $plain = $request->bearerToken();
        if (! $plain || ! PersonalAccessToken::findToken($plain)) {
            return $this->error('Token inválido o expirado.', null, 401);
        }

        $agente = AgenteParqueo::where('user_id', $request->user()->id)->first();

        if (! $agente) {
            return $this->error('El usuario autenticado no es un agente de parqueo.', null, 403);
        }

        return $this->exito(
            new AgenteParqueoResource($agente->load(['user', 'asignaciones.zona'])),
            'Perfil del agente.'
        );
    }
}
