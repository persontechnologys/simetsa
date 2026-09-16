<?php

// app/Models/NotificacionInfraccion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Boleta digital generada cuando un agente registra una infracción a un conductor conocido.
 *
 * Se crea automáticamente en InfraccionService::registrar() cuando conductor_id no es null.
 * La constraint UNIQUE (infraccion_id, conductor_id) garantiza idempotencia.
 *
 * @property int                 $id
 * @property int                 $infraccion_id
 * @property int                 $conductor_id
 * @property \Carbon\Carbon|null $leida_at
 * @property bool                $enviada_push
 */
class NotificacionInfraccion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones_infraccion';

    protected $fillable = [
        'infraccion_id',
        'conductor_id',
        'leida_at',
        'enviada_push',
    ];

    protected $casts = [
        'leida_at'     => 'datetime',
        'enviada_push' => 'boolean',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    /** @return BelongsTo<Infraccion, NotificacionInfraccion> */
    public function infraccion(): BelongsTo
    {
        return $this->belongsTo(Infraccion::class);
    }

    /** @return BelongsTo<Conductor, NotificacionInfraccion> */
    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Indica si la notificación ya fue leída por el conductor. */
    public function estaLeida(): bool
    {
        return $this->leida_at !== null;
    }
}
