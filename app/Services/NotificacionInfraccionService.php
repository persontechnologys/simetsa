<?php

// app/Services/NotificacionInfraccionService.php

namespace App\Services;

use App\Models\DispositivoMovil;
use App\Models\Infraccion;
use App\Models\NotificacionInfraccion;
use Illuminate\Support\Facades\Log;

/**
 * Genera y envía la boleta digital cuando se registra una infracción a un conductor conocido.
 *
 * El push FCM es best-effort: si falla, el registro de la notificación se mantiene
 * con enviada_push=false para reintento futuro. El error no interrumpe el flujo principal.
 */
class NotificacionInfraccionService
{
    public function __construct(private readonly FCMService $fcm)
    {
    }

    /**
     * Crea la boleta digital y trata de enviar el push FCM al conductor.
     *
     * Solo actúa si la infracción tiene conductor_id. Si ya existe una notificación
     * para esa infracción + conductor (por la constraint UNIQUE), devuelve null.
     *
     * @param  Infraccion  $infraccion
     * @return NotificacionInfraccion|null
     */
    public function notificar(Infraccion $infraccion): ?NotificacionInfraccion
    {
        if ($infraccion->conductor_id === null) {
            return null;
        }

        // Idempotente: si ya existe, no crear otra
        $existente = NotificacionInfraccion::where('infraccion_id', $infraccion->id)
            ->where('conductor_id', $infraccion->conductor_id)
            ->first();

        if ($existente) {
            return $existente;
        }

        $notificacion = NotificacionInfraccion::create([
            'infraccion_id' => $infraccion->id,
            'conductor_id'  => $infraccion->conductor_id,
            'enviada_push'  => false,
        ]);

        // Best-effort: intentar push sin interrumpir el flujo si falla
        $this->intentarPush($infraccion, $notificacion);

        return $notificacion;
    }

    /**
     * Intenta enviar el push FCM a todos los dispositivos activos del conductor.
     * Si el envío falla por cualquier causa, registra el error y continúa.
     *
     * @param  Infraccion              $infraccion
     * @param  NotificacionInfraccion  $notificacion
     */
    private function intentarPush(Infraccion $infraccion, NotificacionInfraccion $notificacion): void
    {
        try {
            $conductor = $infraccion->conductor;
            if (! $conductor) {
                return;
            }

            $tokens = DispositivoMovil::where('user_id', $conductor->user_id)
                ->where('activo', true)
                ->where('canal', DispositivoMovil::CANAL_FCM)
                ->pluck('token_fcm');

            if ($tokens->isEmpty()) {
                return;
            }

            $titulo  = 'Infracción SIMETSA';
            $cuerpo  = "Placa {$infraccion->placa}: {$infraccion->tipo_infraccion->etiqueta()}. Multa: \${$infraccion->monto_multa}";
            $datos   = [
                'tipo'         => 'infraccion',
                'infraccion_id'=> (string) $infraccion->id,
            ];

            foreach ($tokens as $token) {
                $this->fcm->enviar($token, $titulo, $cuerpo, $datos);
            }

            $notificacion->update(['enviada_push' => true]);
        } catch (\Throwable $e) {
            Log::warning('NotificacionInfraccionService: push FCM no enviado.', [
                'infraccion_id' => $infraccion->id,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}
