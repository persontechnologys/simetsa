<?php

/**
 * app/Enums/EstadoTurno.php
 *
 * Ciclo de vida del turno del agente de parqueo (Art. 38 — Ordenanza SIMETSA).
 */

namespace App\Enums;

enum EstadoTurno: string
{
    /** Turno iniciado, agente en calle. */
    case Iniciado = 'iniciado';

    /** Turno finalizado por el agente o por el sistema. */
    case Finalizado = 'finalizado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Iniciado   => 'En turno',
            self::Finalizado => 'Finalizado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Iniciado   => 'success',
            self::Finalizado => 'secondary',
        };
    }
}
