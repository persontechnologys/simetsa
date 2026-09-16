<?php
// app/Services/integraciones/AntService.php

namespace App\Services\integraciones;

use Illuminate\Support\Facades\Log;

/**
 * Stub de integración con la ANT — Agencia Nacional de Tránsito.
 *
 * Permite verificar si una placa vehicular está activa en los registros
 * de la ANT y obtener datos básicos del propietario. En Fase 10 este stub
 * será reemplazado por la integración HTTP real con la API de la ANT.
 *
 * La interfaz (firma de métodos) debe mantenerse estable para que
 * VehiculoService no necesite cambios al implementar la integración real.
 *
 * STUB — reemplazar con HTTP real en Fase 10.
 */
class AntService
{
    /**
     * Valida si una placa vehicular está activa en los registros de la ANT
     * y retorna datos básicos del propietario.
     *
     * STUB: no realiza llamadas HTTP. Registra el intento en el log y retorna
     * siempre ['activa' => true, 'propietario' => 'Datos ANT no disponibles'].
     *
     * @param  string  $placa  Placa vehicular (formato alfanumérico ecuatoriano).
     * @return array{activa: bool, propietario: string|null}
     */
    public function validarPlaca(string $placa): array
    {
        Log::info('ANT stub — validación de placa', [
            'placa' => strtoupper($placa),
        ]);

        // STUB: retorna siempre activa sin datos reales del propietario.
        return ['activa' => true, 'propietario' => 'Datos ANT no disponibles'];
    }
}
