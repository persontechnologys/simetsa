<?php

// app/Http/Controllers/Api/ComprobanteApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ComprobanteResource;
use App\Models\Comprobante;
use App\Services\ComprobanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Endpoints de Comprobantes para la app móvil (Art. 19 — Ordenanza SIMETSA).
 *
 * GET /api/v1/comprobantes/{comprobante}           — ver comprobante (auth)
 * GET /api/v1/comprobantes/{comprobante}/pdf       — HTML imprimible (auth)
 * GET /api/v1/comprobantes/{comprobante}/link      — genera URL temporal (auth)
 * GET /api/v1/comprobantes/ver/{token}             — sirve HTML con token (público)
 */
class ComprobanteApiController extends ApiController
{
    public function __construct(private readonly ComprobanteService $comprobanteService)
    {
        $this->middleware('auth:sanctum')->except('pdfPublico');
        $this->middleware('permission:comprobantes.ver', ['only' => ['show', 'pdf', 'generarLink']]);
    }

    /**
     * Muestra un comprobante de pago.
     */
    public function show(Comprobante $comprobante): JsonResponse
    {
        $this->authorize('view', $comprobante);
        $comprobante->load('concepto');
        return $this->exito(new ComprobanteResource($comprobante));
    }

    /**
     * Descarga el HTML imprimible del comprobante (requiere auth Bearer).
     */
    public function pdf(Comprobante $comprobante): Response
    {
        $this->authorize('view', $comprobante);
        $html = $this->comprobanteService->obtenerContenidoPdf($comprobante);
        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=UTF-8',
            'Content-Disposition' => "inline; filename=\"comprobante-{$comprobante->numero}.html\"",
        ]);
    }

    /**
     * Genera una URL temporal (5 min, un solo uso) para abrir el comprobante
     * en el navegador sin autenticación Bearer.
     */
    public function generarLink(Comprobante $comprobante): JsonResponse
    {
        $this->authorize('view', $comprobante);

        $token = Str::random(48);
        Cache::put("comp_pdf:{$token}", $comprobante->id, now()->addMinutes(5));

        $base = rtrim(config('app.url'), '/');
        $url  = "{$base}/api/v1/comprobantes/ver/{$token}";

        return $this->exito(['url' => $url], 'Enlace generado.');
    }

    /**
     * Sirve el HTML del comprobante usando el token temporal de un solo uso.
     * No requiere autenticación Bearer — el token actúa como credencial efímera.
     */
    public function pdfPublico(Request $request, string $token): Response
    {
        $id = Cache::pull("comp_pdf:{$token}");

        if (! $id) {
            abort(403, 'Enlace inválido o expirado. Generá uno nuevo desde la app.');
        }

        $comprobante = Comprobante::findOrFail($id);
        $html        = $this->comprobanteService->obtenerContenidoPdf($comprobante);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
