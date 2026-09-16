<?php

// app/Models/Infraccion.php

namespace App\Models;

use App\Contracts\Cobrable;
use App\Enums\EstadoInfraccion;
use App\Enums\TipoInfraccion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Infracción a la Ordenanza SIMETSA registrada por un Agente de Parqueo.
 *
 * Ciclo de vida:
 *   pendiente → (conductor paga) → pagada
 *   pendiente → (comisario anula) → anulada
 *
 * Implementa Cobrable para que PagoManager pueda cobrar la multa
 * usando la misma arquitectura multi-proveedor de Fase 6.
 *
 * Arts. 15, 17, 18, 28, 29, 30.
 *
 * @property int                    $id
 * @property string                 $placa
 * @property int|null               $conductor_id
 * @property int                    $zona_id
 * @property int|null               $calle_id
 * @property int                    $agente_parqueo_id
 * @property int|null               $ticket_id
 * @property TipoInfraccion         $tipo_infraccion
 * @property EstadoInfraccion       $estado
 * @property float                  $monto_multa
 * @property float                  $sbu_vigente
 * @property int|null               $minutos_excedidos
 * @property string|null            $descripcion
 * @property string|null            $foto_evidencia
 * @property float|null             $latitud
 * @property float|null             $longitud
 * @property string|null            $motivo_anulacion
 * @property int|null               $anulada_por
 * @property \Carbon\Carbon|null    $anulada_en
 */
class Infraccion extends Model implements Cobrable
{
    use HasFactory, SoftDeletes;

    protected $table = 'infracciones';

    protected $fillable = [
        'placa',
        'conductor_id',
        'zona_id',
        'calle_id',
        'agente_parqueo_id',
        'ticket_id',
        'tipo_infraccion',
        'estado',
        'monto_multa',
        'sbu_vigente',
        'minutos_excedidos',
        'descripcion',
        'foto_evidencia',
        'latitud',
        'longitud',
        'motivo_anulacion',
        'anulada_por',
        'anulada_en',
    ];

    protected $casts = [
        'tipo_infraccion'   => TipoInfraccion::class,
        'estado'            => EstadoInfraccion::class,
        'monto_multa'       => 'decimal:2',
        'sbu_vigente'       => 'decimal:2',
        'minutos_excedidos' => 'integer',
        'latitud'           => 'decimal:7',
        'longitud'          => 'decimal:7',
        'anulada_en'        => 'datetime',
    ];

    // ── Mutators ─────────────────────────────────────────────────────────────

    /** Normaliza la placa a mayúsculas al asignarla. */
    public function setPlacaAttribute(string $value): void
    {
        $this->attributes['placa'] = strtoupper(trim($value));
    }

    // ── Relaciones ───────────────────────────────────────────────────────────

    /** @return BelongsTo<AgenteParqueo, Infraccion> */
    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteParqueo::class, 'agente_parqueo_id');
    }

    /** @return BelongsTo<Conductor, Infraccion> */
    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class);
    }

    /** @return BelongsTo<Zona, Infraccion> */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    /** @return BelongsTo<Calle, Infraccion> */
    public function calle(): BelongsTo
    {
        return $this->belongsTo(Calle::class);
    }

    /** @return BelongsTo<Ticket, Infraccion> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, Infraccion> */
    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    /**
     * Inmovilización asociada (1:1, nullable — Art. 15).
     *
     * @return HasOne<Inmovilizacion>
     */
    public function inmovilizacion(): HasOne
    {
        return $this->hasOne(Inmovilizacion::class);
    }

    /**
     * Transacciones de pago polimórficas (Fase 6 — PagoManager).
     * La clave morph es 'concepto' (concepto_type / concepto_id en transacciones_pago).
     *
     * @return MorphMany<TransaccionPago>
     */
    public function transacciones(): MorphMany
    {
        return $this->morphMany(TransaccionPago::class, 'concepto');
    }

    /** Comprobante de pago generado al acreditar la multa (Art. 19). */
    public function comprobante(): MorphOne
    {
        return $this->morphOne(Comprobante::class, 'concepto');
    }

    /** Órdenes de pago formales emitidas para esta infracción (Art. 28). */
    public function ordenesPago(): HasMany
    {
        return $this->hasMany(OrdenPago::class);
    }

    /**
     * Impugnación presentada por el conductor (Art. 17.f).
     * Una infracción solo puede tener una impugnación por conductor.
     *
     * @return HasOne<Impugnacion>
     */
    public function impugnacion(): HasOne
    {
        return $this->hasOne(Impugnacion::class);
    }

    /**
     * Boletas digitales generadas al registrar la infracción.
     *
     * @return HasMany<NotificacionInfraccion>
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(NotificacionInfraccion::class);
    }

    // ── Cobrable ─────────────────────────────────────────────────────────────

    /**
     * Monto de la multa a cobrar (Art. 28, 29, 30).
     */
    public function montoCobrable(): float
    {
        return (float) $this->monto_multa;
    }

    /**
     * Descripción del cobro para el gateway de pagos.
     */
    public function descripcionCobro(): string
    {
        return sprintf(
            'Multa SIMETSA — %s — Placa: %s',
            $this->tipo_infraccion->etiqueta(),
            $this->placa,
        );
    }

    /**
     * Acredita el pago de la multa:
     * - Marca la infracción como pagada.
     * - Libera la inmovilización si existe (Art. 15).
     *
     * @param  TransaccionPago  $transaccion  Transacción con estado Completada.
     */
    public function acreditar(TransaccionPago $transaccion): void
    {
        $this->estado = EstadoInfraccion::Pagada;
        $this->save();

        if ($this->inmovilizacion && $this->inmovilizacion->estado->value === 'activa') {
            $this->inmovilizacion->update([
                'estado'     => \App\Enums\EstadoInmovilizacion::Liberada,
                'liberada_en' => now(),
            ]);
        }
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /** @param \Illuminate\Database\Eloquent\Builder $query */
    public function scopePendientes($query): void
    {
        $query->where('estado', EstadoInfraccion::Pendiente);
    }

    /** @param \Illuminate\Database\Eloquent\Builder $query */
    public function scopePorPlaca($query, string $placa): void
    {
        $query->where('placa', strtoupper(trim($placa)));
    }
}
