<?php
// app/Models/Vehiculo.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vehículo registrado por un conductor (Art. 25 Ordenanza SIMETSA).
 *
 * Un conductor puede tener múltiples vehículos. La placa es única entre los
 * vehículos no eliminados (índice parcial PostgreSQL en la migración).
 *
 * @property int         $id
 * @property int         $conductor_id
 * @property int         $tipo_vehiculo_id
 * @property string      $placa
 * @property string      $marca
 * @property string      $modelo
 * @property int         $anio
 * @property string      $color
 * @property string      $estado
 * @property string|null $observaciones
 */
class Vehiculo extends Model
{
    use HasFactory, SoftDeletes;

    public const ESTADO_ACTIVO   = 'activo';
    public const ESTADO_INACTIVO = 'inactivo';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'conductor_id', 'tipo_vehiculo_id', 'placa', 'marca',
        'modelo', 'anio', 'color', 'estado', 'observaciones',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'anio' => 'integer',
    ];

    /**
     * Estados posibles para selects y etiquetas.
     *
     * @return array<string, string>
     */
    public static function listadoEstados(): array
    {
        return [
            self::ESTADO_ACTIVO   => 'Activo',
            self::ESTADO_INACTIVO => 'Inactivo',
        ];
    }

    /** Conductor propietario del vehículo. */
    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class);
    }

    /** Tipo de vehículo del catálogo (Art. 25). */
    public function tipoVehiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVehiculo::class);
    }

    /** Etiqueta legible del estado para vistas Blade. */
    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_ACTIVO   => 'Activo',
            self::ESTADO_INACTIVO => 'Inactivo',
            default               => ucfirst($this->estado),
        };
    }

    /** Color Bootstrap del badge de estado. */
    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_ACTIVO   => 'success',
            self::ESTADO_INACTIVO => 'secondary',
            default               => 'dark',
        };
    }
}

