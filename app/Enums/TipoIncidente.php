<?php

/**
 * app/Enums/TipoIncidente.php
 *
 * Tipos de incidente que el agente puede reportar desde la calle.
 * Art. 38.m — el agente debe comunicar a ECU 911 ante incidentes de tráfico.
 */

namespace App\Enums;

enum TipoIncidente: string
{
    case Accidente  = 'accidente';
    case Obstruccion = 'obstruccion';
    case Conflicto  = 'conflicto';
    case Otro       = 'otro';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Accidente   => 'Accidente de tránsito',
            self::Obstruccion => 'Obstrucción vial',
            self::Conflicto   => 'Conflicto con conductor',
            self::Otro        => 'Otro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Accidente   => 'danger',
            self::Obstruccion => 'warning',
            self::Conflicto   => 'info',
            self::Otro        => 'secondary',
        };
    }

    /** @return array<string,string> */
    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($c) => [$c->value => $c->etiqueta()])
            ->all();
    }
}
