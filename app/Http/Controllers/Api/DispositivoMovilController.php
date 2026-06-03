<?php

// app/Http/Controllers/Api/DispositivoMovilController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreDispositivoMovilRequest;
use App\Http\Resources\DispositivoMovilResource;
use App\Models\DispositivoMovil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registro de dispositivos móviles para notificaciones push vía FCM.
 *
 * En Fase 5, solo se persiste el token. El envío real se activa en Fase 6.
 * Si el par (user_id, token_fcm) ya existe, se reutiliza (idempotente).
 */
class DispositivoMovilController extends ApiController
{
    /**
     * Registra o actualiza el token FCM del dispositivo del usuario autenticado.
     *
     * Endpoint idempotente: si el token ya existe para el usuario, solo actualiza
     * la plataforma y el timestamp de último uso.
     *
     * @param  \App\Http\Requests\StoreDispositivoMovilRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreDispositivoMovilRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $dispositivo = DispositivoMovil::updateOrCreate(
            [
                'user_id'   => $request->user()->id,
                'token_fcm' => $datos['token_fcm'],
            ],
            [
                'plataforma'         => $datos['plataforma'],
                'canal'              => $datos['canal'] ?? DispositivoMovil::CANAL_FCM,
                'tipo_app'           => $datos['tipo_app'] ?? null,
                'modelo_dispositivo' => $datos['modelo_dispositivo'] ?? null,
                'activo'             => true,
                'ultimo_uso_at'      => now(),
            ]
        );

        return $this->exito(
            new DispositivoMovilResource($dispositivo),
            'Token FCM registrado correctamente.',
            $dispositivo->wasRecentlyCreated ? 201 : 200,
        );
    }

    /**
     * Elimina (desactiva) el token FCM del dispositivo del usuario autenticado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $token   Token FCM a eliminar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $token): JsonResponse
    {
        $eliminado = DispositivoMovil::where('user_id', $request->user()->id)
            ->where('token_fcm', $token)
            ->delete();

        if (! $eliminado) {
            return $this->error('Token no encontrado para este usuario.', null, 404);
        }

        return $this->exito(null, 'Token FCM eliminado correctamente.');
    }
}
