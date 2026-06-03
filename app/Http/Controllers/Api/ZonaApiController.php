<?php
// app/Http/Controllers/Api/ZonaApiController.php

namespace App\Http\Controllers\Api;

use App\Models\Zona;
use Illuminate\Http\JsonResponse;

/**
 * Expone el catálogo de zonas activas para la app móvil (Fase 9.C).
 *
 * La app necesita la lista de zonas y calles para el formulario de
 * compra de tickets (POST /api/v1/tickets requiere zona_id y calle_id opcional).
 */
class ZonaApiController extends ApiController
{
    /**
     * Lista todas las zonas activas con sus calles ordenadas.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $zonas = Zona::activas()
            ->with(['calles' => fn ($q) => $q->select('id', 'zona_id', 'codigo', 'nombre')->orderBy('nombre')])
            ->get(['id', 'codigo', 'nombre']);

        return $this->exito($zonas, 'Zonas activas.');
    }
}
