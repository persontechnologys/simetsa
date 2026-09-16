<?php

// app/Models/Impugnacion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Impugnación de infracción presentada digitalmente por un conductor (Art. 17.f).
 *
 * Ciclo de vida:
 *   pendiente → admitida → resuelta
 *   pendiente → rechazada
 *
 * @property int             $id
 * @property int             $infraccion_id
 * @property int             $conductor_id
 * @property string          $motivo
 * @property string          $estado        pendiente|admitida|rechazada|resuelta
 * @property string|null     $resolucion
 * @property int|null        $resuelto_por
 * @property \Carbon\Carbon|null $resuelto_at
 */
class Impugnacion extends Model
{
    use HasFactory;

    protected $table = 'impugnaciones';

    public const ESTADO_PENDIENTE  = 'pendiente';
    public const ESTADO_ADMITIDA   = 'admitida';
    public const ESTADO_RECHAZADA  = 'rechazada';
    public const ESTADO_RESUELTA   = 'resuelta';

    protected $fillable = [
        'infraccion_id',
        'conductor_id',
        'motivo',
        'estado',
        'resolucion',
        'resuelto_por',
        'resuelto_at',
    ];

    protected $casts = [
        'resuelto_at' => 'datetime',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    /** @return BelongsTo<Infraccion, Impugnacion> */
    public function infraccion(): BelongsTo
    {
        return $this->belongsTo(Infraccion::class);
    }

    /** @return BelongsTo<Conductor, Impugnacion> */
    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class);
    }

    /** @return BelongsTo<User, Impugnacion> */
    public function resolutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Estados disponibles con su etiqueta para selects y badges. */
    public static function estados(): array
    {
        return [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_ADMITIDA  => 'Admitida',
            self::ESTADO_RECHAZADA => 'Rechazada',
            self::ESTADO_RESUELTA  => 'Resuelta',
        ];
    }

    /** Clase Bootstrap del badge según el estado. */
    public function colorBadge(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => 'warning',
            self::ESTADO_ADMITIDA  => 'info',
            self::ESTADO_RECHAZADA => 'danger',
            self::ESTADO_RESUELTA  => 'success',
            default                => 'secondary',
        };
    }

    /** Indica si la impugnación puede recibir una resolución. */
    public function esResoluble(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_ADMITIDA]);
    }
}
