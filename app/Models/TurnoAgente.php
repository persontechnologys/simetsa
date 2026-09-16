<?php
// app/Models/TurnoAgente.php

namespace App\Models;

use App\Enums\EstadoTurno;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Turno de trabajo del agente de parqueo en calle (Art. 38 — Ordenanza SIMETSA).
 *
 * @property int             $id
 * @property int             $agente_parqueo_id
 * @property \Carbon\Carbon  $inicio_at
 * @property \Carbon\Carbon|null $fin_at
 * @property EstadoTurno     $estado
 * @property string|null     $observaciones
 */
class TurnoAgente extends Model
{
    use HasFactory;

    protected $table = 'turnos_agente';

    protected $fillable = [
        'agente_parqueo_id',
        'inicio_at',
        'fin_at',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'inicio_at' => 'datetime',
        'fin_at'    => 'datetime',
        'estado'    => EstadoTurno::class,
    ];

    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteParqueo::class, 'agente_parqueo_id');
    }

    public function recorridos(): HasMany
    {
        return $this->hasMany(RecorridoAgente::class);
    }

    public function incidentes(): HasMany
    {
        return $this->hasMany(IncidenteCalle::class);
    }

    /** Duración del turno en minutos (null si aún activo). */
    public function duracionMinutos(): ?int
    {
        if (! $this->fin_at) {
            return null;
        }

        return (int) $this->inicio_at->diffInMinutes($this->fin_at);
    }

    /** Duración transcurrida en minutos desde el inicio (turno activo). */
    public function minutosTranscurridos(): int
    {
        return (int) $this->inicio_at->diffInMinutes(now());
    }

    public function estaActivo(): bool
    {
        return $this->estado === EstadoTurno::Iniciado;
    }
}
