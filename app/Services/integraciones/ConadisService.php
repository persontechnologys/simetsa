<?php
// app/Services/integraciones/ConadisService.php

namespace App\Services\integraciones;

use Illuminate\Support\Facades\Log;

/**
 * Stub de integración con el CONADIS (Art. 26 — Ordenanza SIMETSA).
 *
 * El Art. 26 reconoce exoneración total a personas con discapacidad acreditadas
 * por el CONADIS con porcentaje ≥ 30%. En Fase 10 este stub será reemplazado
 * por la integración HTTP real con la API del CONADIS / SRI.
 *
 * La interfaz (firma de métodos) debe mantenerse estable para que
 * CredencialDiscapacidadService no necesite cambios al implementar la integración real.
 *
 * STUB — reemplazar con HTTP real en Fase 10.
 */
class ConadisService
{
    /**
     * Valida si una cédula está registrada en el CONADIS y retorna el porcentaje
     * de discapacidad reconocido.
     *
     * STUB: no realiza llamadas HTTP. Registra el intento en el log y retorna
     * siempre ['valido' => true, 'porcentaje' => 40] para que el flujo continúe.
     *
     * @param  string  $cedula  Cédula de identidad ecuatoriana (10 dígitos).
     * @return array{valido: bool, porcentaje: int|null}
     */
    public function validarCedula(string $cedula): array
    {
        Log::info('CONADIS stub — validación de cédula', [
            'cedula' => $cedula,
        ]);

        // STUB: retorna siempre válido con porcentaje mínimo de exoneración (Art. 26).
        return ['valido' => true, 'porcentaje' => 40];
    }
}
