<?php
// app/Models/CredencialDiscapacidad.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Credencial CONADIS de un conductor (Art. 26 Ordenanza SIMETSA).
 *
 * Un vehículo puede tener historial de credenciales rechazadas, pero solo
 * una activa (pendiente o aprobada) a la vez. La regla se controla en
 * CredencialDiscapacidadService::solicitar().
 *
 * @property int         $id
 * @property int         $conductor_id
 * @property string      $numero_conadis
 * @property int|null    $porcentaje_discapacidad
 * @property string      $nombre_beneficiario
 * @property \Carbon\Carbon $fecha_emision
 * @property \Carbon\Carbon|null $fecha_vencimiento
 * @property string|null $ruta_archivo
 * @property string      $estado
 * @property string|null $observaciones
 * @property int|null    $aprobada_por
 * @property \Carbon\Carbon|null $fecha_aprobacion
 */
class CredencialDiscapacidad extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'credenciales_discapacidad';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADA  = 'aprobada';
    public const ESTADO_RECHAZADA = 'rechazada';
    public const ESTADO_VENCIDA   = 'vencida';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'conductor_id', 'numero_conadis', 'porcentaje_discapacidad',
        'nombre_beneficiario', 'fecha_emision', 'fecha_vencimiento',
        'ruta_archivo', 'estado', 'observaciones',
        'aprobada_por', 'fecha_aprobacion',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_emision'    => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_aprobacion' => 'datetime',
    ];

    /**
     * Conductor al que pertenece esta credencial.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class);
    }

    /**
     * Usuario (comisario o director) que aprobó la credencial.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function aprobadaPorUsuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobada_por');
    }

    /**
     * Indica si la credencial bloquea el registro de una nueva (Art. 26).
     *
     * @return bool
     */
    public function estaActiva(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_APROBADA], true);
    }
}
