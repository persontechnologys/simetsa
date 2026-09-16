<?php
// app/Models/RecorridoAgente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Punto GPS del recorrido del agente durante su turno (Art. 38 — Ordenanza SIMETSA).
 *
 * @property int             $id
 * @property int             $turno_agente_id
 * @property float           $latitud
 * @property float           $longitud
 * @property \Carbon\Carbon  $registrado_at
 */
class RecorridoAgente extends Model
{
    use HasFactory;

    protected $table = 'recorridos_agente';

    protected $fillable = [
        'turno_agente_id',
        'latitud',
        'longitud',
        'registrado_at',
    ];

    protected $casts = [
        'latitud'       => 'float',
        'longitud'      => 'float',
        'registrado_at' => 'datetime',
    ];

    public function turno(): BelongsTo
    {
        return $this->belongsTo(TurnoAgente::class, 'turno_agente_id');
    }
}
