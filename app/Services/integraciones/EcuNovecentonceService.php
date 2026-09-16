<?php
// app/Services/integraciones/EcuNovecentonceService.php

namespace App\Services\integraciones;

use App\Models\IncidenteCalle;
use Illuminate\Support\Facades\Log;

/**
 * Stub de integración con ECU 911 (Art. 38.m — Ordenanza SIMETSA).
 *
 * En Fase 10 este stub será reemplazado por la integración HTTP real
 * con la API de ECU 911. Por ahora registra un log y retorna true.
 *
 * La interfaz (firma de métodos) debe mantenerse estable para que
 * FiscalizacionService no necesite cambios cuando se implemente la integración real.
 */
class EcuNovecentonceService
{
    /**
     * Notifica un incidente de calle al ECU 911.
     *
     * STUB: no realiza llamadas HTTP. Registra el intento en el log
     * y retorna true para que el flujo continúe normalmente.
     *
     * @param  IncidenteCalle  $incidente
     * @return bool  true si la notificación fue aceptada (siempre true en stub)
     */
    public function notificar(IncidenteCalle $incidente): bool
    {
        Log::info('ECU911 stub — notificación de incidente', [
            'incidente_id' => $incidente->id,
            'tipo'         => $incidente->tipo->value,
            'descripcion'  => $incidente->descripcion,
            'latitud'      => $incidente->latitud,
            'longitud'     => $incidente->longitud,
            'turno_id'     => $incidente->turno_agente_id,
        ]);

        return true;
    }
}
