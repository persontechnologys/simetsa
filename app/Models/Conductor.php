<?php
// app/Models/Conductor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Conductor — usuario final del SIMETSA (Fase 4).
 *
 * Registro 1:1 con User (mismo patrón que AgenteParqueo y PuntoVenta). Los
 * datos personales viven en PerfilUsuario; aquí se guarda el estado de la
 * cuenta de conductor y, más adelante, sus vehículos (4.B).
 */
class Conductor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'conductores';

    public const ESTADO_ACTIVO    = 'activo';
    public const ESTADO_BLOQUEADO = 'bloqueado';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['user_id', 'codigo', 'estado'];

    /**
     * Vehículos del conductor (1:N, Art. 25).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class);
    }

    /**
     * Credencial CONADIS activa del conductor (Art. 26).
     * Devuelve la más reciente (pendiente, aprobada o rechazada).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function credencial(): HasOne
    {
        return $this->hasOne(CredencialDiscapacidad::class)->latest('id');
    }

    /**
     * Cuenta de usuario asociada (1:1).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Estados posibles para selects y etiquetas.
     *
     * @return array<string, string>
     */
    public static function listadoEstados(): array
    {
        return [
            self::ESTADO_ACTIVO    => 'Activo',
            self::ESTADO_BLOQUEADO => 'Bloqueado',
        ];
    }
}