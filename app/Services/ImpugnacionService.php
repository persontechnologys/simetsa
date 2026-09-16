<?php

// app/Services/ImpugnacionService.php

namespace App\Services;

use App\Enums\EstadoInfraccion;
use App\Models\Conductor;
use App\Models\Impugnacion;
use App\Models\Infraccion;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio del módulo de impugnaciones de infracciones.
 *
 * El conductor puede impugnar una infracción en estado pendiente (Art. 17.f).
 * El comisario puede admitir, rechazar o resolver la impugnación.
 */
class ImpugnacionService
{
    /**
     * El conductor presenta una impugnación formal de la infracción.
     *
     * Validaciones:
     * - La infracción debe estar en estado pendiente (Art. 17.f).
     * - No puede existir impugnación previa para esa infracción + conductor.
     *
     * @param  Infraccion  $infraccion
     * @param  Conductor   $conductor
     * @param  string      $motivo
     * @return Impugnacion
     *
     * @throws DomainException
     */
    public function presentar(Infraccion $infraccion, Conductor $conductor, string $motivo): Impugnacion
    {
        if ($infraccion->estado !== EstadoInfraccion::Pendiente) {
            throw new DomainException(
                'Solo se puede impugnar una infracción en estado pendiente (Art. 17.f).'
            );
        }

        $yaExiste = Impugnacion::where('infraccion_id', $infraccion->id)
            ->where('conductor_id', $conductor->id)
            ->exists();

        if ($yaExiste) {
            throw new DomainException('Ya existe una impugnación presentada para esta infracción.');
        }

        if (empty(trim($motivo))) {
            throw new DomainException('El motivo de la impugnación es obligatorio.');
        }

        return DB::transaction(function () use ($infraccion, $conductor, $motivo) {
            return Impugnacion::create([
                'infraccion_id' => $infraccion->id,
                'conductor_id'  => $conductor->id,
                'motivo'        => trim($motivo),
                'estado'        => Impugnacion::ESTADO_PENDIENTE,
            ]);
        });
    }

    /**
     * El comisario admite la impugnación para análisis formal.
     *
     * @param  Impugnacion  $impugnacion
     * @param  User         $usuario
     * @return Impugnacion
     *
     * @throws DomainException
     */
    public function admitir(Impugnacion $impugnacion, User $usuario): Impugnacion
    {
        if ($impugnacion->estado !== Impugnacion::ESTADO_PENDIENTE) {
            throw new DomainException(
                "Solo se puede admitir una impugnación en estado pendiente (estado actual: {$impugnacion->estado})."
            );
        }

        return DB::transaction(function () use ($impugnacion, $usuario) {
            $impugnacion->update([
                'estado'      => Impugnacion::ESTADO_ADMITIDA,
                'resuelto_por'=> $usuario->id,
                'resuelto_at' => now(),
            ]);

            return $impugnacion->fresh();
        });
    }

    /**
     * El comisario rechaza la impugnación con una resolución motivada.
     *
     * @param  Impugnacion  $impugnacion
     * @param  User         $usuario
     * @param  string       $resolucion
     * @return Impugnacion
     *
     * @throws DomainException
     */
    public function rechazar(Impugnacion $impugnacion, User $usuario, string $resolucion): Impugnacion
    {
        if (! in_array($impugnacion->estado, [Impugnacion::ESTADO_PENDIENTE, Impugnacion::ESTADO_ADMITIDA])) {
            throw new DomainException(
                "La impugnación en estado '{$impugnacion->estado}' no puede ser rechazada."
            );
        }

        if (empty(trim($resolucion))) {
            throw new DomainException('La resolución del rechazo es obligatoria.');
        }

        return DB::transaction(function () use ($impugnacion, $usuario, $resolucion) {
            $impugnacion->update([
                'estado'       => Impugnacion::ESTADO_RECHAZADA,
                'resolucion'   => trim($resolucion),
                'resuelto_por' => $usuario->id,
                'resuelto_at'  => now(),
            ]);

            return $impugnacion->fresh();
        });
    }

    /**
     * El comisario resuelve la impugnación a favor del conductor.
     *
     * Registra la resolución y marca la impugnación como resuelta.
     * La anulación de la infracción (si corresponde) es una decisión
     * administrativa separada que el comisario ejecuta desde el módulo de infracciones.
     *
     * @param  Impugnacion  $impugnacion
     * @param  User         $usuario
     * @param  string       $resolucion
     * @return Impugnacion
     *
     * @throws DomainException
     */
    public function resolver(Impugnacion $impugnacion, User $usuario, string $resolucion): Impugnacion
    {
        if (! $impugnacion->esResoluble()) {
            throw new DomainException(
                "La impugnación en estado '{$impugnacion->estado}' no admite resolución."
            );
        }

        if (empty(trim($resolucion))) {
            throw new DomainException('La resolución es obligatoria.');
        }

        return DB::transaction(function () use ($impugnacion, $usuario, $resolucion) {
            $impugnacion->update([
                'estado'       => Impugnacion::ESTADO_RESUELTA,
                'resolucion'   => trim($resolucion),
                'resuelto_por' => $usuario->id,
                'resuelto_at'  => now(),
            ]);

            return $impugnacion->fresh();
        });
    }
}
