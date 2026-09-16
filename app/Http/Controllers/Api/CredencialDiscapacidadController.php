<?php
// app/Http/Controllers/Api/CredencialDiscapacidadController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CredencialDiscapacidadStoreRequest;
use App\Http\Resources\CredencialDiscapacidadResource;
use App\Models\Conductor;
use App\Models\CredencialDiscapacidad;
use App\Services\CredencialDiscapacidadService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de credencial CONADIS del conductor desde la app móvil (Art. 26 Ordenanza SIMETSA).
 *
 * La credencial es personal (por conductor, no por vehículo). El comisario o director
 * la aprueba o rechaza desde el backoffice web.
 */
class CredencialDiscapacidadController extends ApiController
{
    public function __construct(private readonly CredencialDiscapacidadService $servicio)
    {
    }

    /**
     * Devuelve la credencial más reciente del conductor autenticado.
     *
     * @see Art. 26 Ordenanza SIMETSA.
     */
    public function show(Request $request): JsonResponse
    {
        if (! $request->user()->can('credenciales_discapacidad.ver')) {
            return $this->error('No autorizado.', null, 403);
        }

        $conductor = Conductor::where('user_id', $request->user()->id)->first();

        if (! $conductor) {
            return $this->error('Perfil de conductor no encontrado.', null, 404);
        }

        $credencial = $conductor->credencial;

        if (! $credencial) {
            return $this->error('No tenés una credencial CONADIS registrada.', null, 404);
        }

        return $this->exito(new CredencialDiscapacidadResource($credencial), 'Credencial del conductor.');
    }

    /**
     * Registra una solicitud de credencial CONADIS para el conductor autenticado.
     *
     * @see Art. 26 Ordenanza SIMETSA.
     */
    public function store(CredencialDiscapacidadStoreRequest $request): JsonResponse
    {
        $this->authorize('create', CredencialDiscapacidad::class);

        $conductor = Conductor::where('user_id', $request->user()->id)->first();

        if (! $conductor) {
            return $this->error('Perfil de conductor no encontrado.', null, 404);
        }

        try {
            $credencial = $this->servicio->solicitar($conductor, $request->validated());
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new CredencialDiscapacidadResource($credencial),
            'Credencial enviada para revisión.',
            201,
        );
    }
}
