<?php

// app/Models/Ticket.php

namespace App\Models;

use App\Contracts\Cobrable;
use App\Enums\EstadoTicket;
use App\Enums\MetodoPago;
use App\Enums\ProveedorPago;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ticket digital de parqueo tarifado (sustituto de "especies valoradas", Art. 19).
 *
 * Ciclo de vida:
 *   Pendiente → (agente inicia sesión) → Activo → Expirado / En tolerancia
 *   PendientePago → (webhook completada) → Pendiente / Activo
 *   Pendiente → (conductor cancela) → Cancelado
 *   Pendiente|Activo|EnTolerancia → (comisario anula) → Anulado
 *
 * Implementa Cobrable para ser cobrado por DeunaPaymentProvider u otros gateways.
 *
 * @property int              $id
 * @property string           $codigo
 * @property int              $conductor_id
 * @property int              $vehiculo_id
 * @property int              $zona_id
 * @property int|null         $calle_id
 * @property int              $horas_compradas
 * @property float            $monto
 * @property EstadoTicket     $estado
 * @property MetodoPago       $metodo_pago
 * @property ProveedorPago    $proveedor
 * @property bool             $es_exonerado
 * @property string|null      $tipo_exoneracion
 * @property \Carbon\Carbon   $comprado_en
 * @property \Carbon\Carbon   $expira_en
 */
class Ticket extends Model implements Cobrable
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo', 'conductor_id', 'vehiculo_id', 'zona_id', 'calle_id',
        'horas_compradas', 'monto', 'estado', 'metodo_pago', 'proveedor',
        'es_exonerado', 'tipo_exoneracion', 'comprado_en', 'expira_en',
    ];

    protected $casts = [
        'estado'          => EstadoTicket::class,
        'metodo_pago'     => MetodoPago::class,
        'proveedor'       => ProveedorPago::class,
        'es_exonerado'    => 'boolean',
        'horas_compradas' => 'integer',
        'monto'           => 'decimal:2',
        'comprado_en'     => 'datetime',
        'expira_en'       => 'datetime',
    ];

    // ────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ────────────────────────────────────────────────────────────────────────

    /** Conductor que compró el ticket. */
    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class);
    }

    /** Vehículo para el que se emitió el ticket. */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /** Zona tarifada donde aplica el ticket. */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    /** Calle específica dentro de la zona (opcional al comprar). */
    public function calle(): BelongsTo
    {
        return $this->belongsTo(Calle::class);
    }

    /** Sesión de parqueo iniciada por el agente (si existe). */
    public function sesion(): HasOne
    {
        return $this->hasOne(SesionParqueo::class);
    }

    /** Registro de cancelación o anulación (si existe). */
    public function cancelacion(): HasOne
    {
        return $this->hasOne(Cancelacion::class);
    }

    /** Transacciones de pago asociadas al ticket (polimórfico). */
    public function transacciones(): MorphMany
    {
        return $this->morphMany(TransaccionPago::class, 'concepto');
    }

    /** Comprobante de pago generado al acreditar el ticket (Art. 19). */
    public function comprobante(): MorphOne
    {
        return $this->morphOne(Comprobante::class, 'concepto');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Cobrable — contrato para el gateway de pagos
    // ────────────────────────────────────────────────────────────────────────

    /** {@inheritdoc} */
    public function montoCobrable(): float
    {
        return (float) $this->monto;
    }

    /** {@inheritdoc} */
    public function descripcionCobro(): string
    {
        return "Ticket de parqueo {$this->codigo} — {$this->horas_compradas}h zona {$this->zona?->nombre}";
    }

    /**
     * {@inheritdoc}
     *
     * Se llama desde el webhook handler cuando el pago se confirma.
     * Transiciona el ticket de PendientePago a Pendiente (listo para que el agente inicie sesión).
     * La notificación push se dispara en Fase 6.C.
     */
    public function acreditar(TransaccionPago $transaccion): void
    {
        if ($this->estado === EstadoTicket::PendientePago) {
            $this->update(['estado' => EstadoTicket::Pendiente]);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Accessors
    // ────────────────────────────────────────────────────────────────────────

    /** Etiqueta legible del estado para vistas Blade y API. */
    public function getEstadoLabelAttribute(): string
    {
        return $this->estado->etiqueta();
    }

    /** Color Bootstrap del badge de estado. */
    public function getEstadoColorAttribute(): string
    {
        return $this->estado->color();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Genera el próximo código de ticket con formato T-YYYY-NNNNN.
     * Usa withTrashed para no reutilizar códigos de tickets eliminados.
     */
    public static function generarCodigo(): string
    {
        $anio    = now()->year;
        $prefijo = "T-{$anio}-";
        $ultimo  = static::withTrashed()
            ->where('codigo', 'like', "{$prefijo}%")
            ->max('codigo');

        $numero = $ultimo ? ((int) substr($ultimo, strlen($prefijo))) + 1 : 1;

        return $prefijo . str_pad((string) $numero, 5, '0', STR_PAD_LEFT);
    }
}
