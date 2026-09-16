<?php
// app/Models/IncidenteCalle.php

namespace App\Models;

use App\Enums\TipoIncidente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Incidente de calle reportado por el agente (Art. 38.m — Ordenanza SIMETSA).
 *
 * El agente tiene obligación de comunicar al ECU 911 ante accidentes,
 * obstrucciones o conflictos que ocurran en su zona de trabajo.
 *
 * @property int             $id
 * @property int             $turno_agente_id
 * @property TipoIncidente   $tipo
 * @property string          $descripcion
 * @property float|null      $latitud
 * @property float|null      $longitud
 * @property string|null     $foto_evidencia
 * @property bool            $reportado_ecu911
 * @property \Carbon\Carbon|null $notificado_at
 */
class IncidenteCalle extends Model
{
    use HasFactory;

    protected $table = 'incidentes_calle';

    protected $fillable = [
        'turno_agente_id',
        'tipo',
        'descripcion',
        'latitud',
        'longitud',
        'foto_evidencia',
        'reportado_ecu911',
        'notificado_at',
    ];

    protected $casts = [
        'tipo'            => TipoIncidente::class,
        'latitud'         => 'float',
        'longitud'        => 'float',
        'reportado_ecu911'=> 'boolean',
        'notificado_at'   => 'datetime',
    ];

    public function turno(): BelongsTo
    {
        return $this->belongsTo(TurnoAgente::class, 'turno_agente_id');
    }
}
