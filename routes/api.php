<?php
// routes/api.php

use App\Http\Controllers\Api\AgenteAuthController;
use App\Http\Controllers\Api\ImpugnacionApiController as ApiImpugnacionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MovilAuthController;
use App\Http\Controllers\Api\IncidenteCalleController as ApiIncidenteCalleController;
use App\Http\Controllers\Api\RecorridoAgenteController as ApiRecorridoAgenteController;
use App\Http\Controllers\Api\TurnoAgenteController as ApiTurnoAgenteController;
use App\Http\Controllers\Api\ZonaApiController;
use App\Http\Controllers\Api\CredencialDiscapacidadController as ApiCredencialDiscapacidadController;
use App\Http\Controllers\Api\DispositivoMovilController as ApiDispositivoMovilController;
use App\Http\Controllers\Api\InfraccionController as ApiInfraccionController;
use App\Http\Controllers\Api\PagoWebhookController;
use App\Http\Controllers\Api\SesionParqueoController as ApiSesionParqueoController;
use App\Http\Controllers\Api\TicketController as ApiTicketController;
use App\Http\Controllers\Api\TipoVehiculoController as ApiTipoVehiculoController;
use App\Http\Controllers\Api\ValidacionTicketController;
use App\Http\Controllers\Api\VehiculoController as ApiVehiculoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — SIMETSA
|--------------------------------------------------------------------------
| Endpoints consumidos por la app móvil (React Native) vía Sanctum.
| Todas las respuestas usan el envelope {exito, mensaje, datos, errores}.
*/

Route::prefix('v1')->group(function () {

    // --- Públicas ---
    Route::post('registro', [AuthController::class, 'registrar'])->name('api.registro');
    Route::post('login',    [AuthController::class, 'login'])->name('api.login');

    // Comprobante PDF público con token de un solo uso (5 min) — generado desde la app autenticada
    Route::get('comprobantes/ver/{token}', [App\Http\Controllers\Api\ComprobanteApiController::class, 'pdfPublico'])
        ->name('api.comprobantes.pdf-publico')
        ->where('token', '[a-zA-Z0-9]+');

    // ===== Fase 9.B — Auth agente de parqueo (legacy, se mantiene) =====
    Route::post('agente/auth/login', [AgenteAuthController::class, 'login'])->name('api.agente.login');

    // ===== Auth móvil unificada — todos los roles habilitados (Fase 9 refactor) =====
    Route::post('movil/login', [MovilAuthController::class, 'login'])->name('api.movil.login'); // URL=> /api/v1/movil/login

    // ===== Fase 6.C — Webhooks de pago (públicos, firmados por el gateway) =====
    Route::post('pagos/webhook/{proveedor}', [PagoWebhookController::class, 'recibir'])
        ->name('api.pagos.webhook')
        ->where('proveedor', '[a-z]+');

    // --- Protegidas (token Sanctum) ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
        Route::get('perfil',  [AuthController::class, 'perfil'])->name('api.perfil');

        // ===== Fase 9.B — Auth agente (legacy, se mantiene) =====
        Route::post('agente/auth/logout', [AgenteAuthController::class, 'logout'])->name('api.agente.logout');
        Route::get('agente/auth/perfil',  [AgenteAuthController::class, 'perfil'])->name('api.agente.perfil');

        // ===== Auth móvil unificada — protegidas (Fase 9 refactor) =====
        Route::post('movil/logout', [MovilAuthController::class, 'logout'])->name('api.movil.logout');
        Route::get('movil/me',      [MovilAuthController::class, 'me'])->name('api.movil.me');

        // ===== Fase 9.C — Catálogo de zonas activas + calles (formulario de ticket) =====
        Route::get('zonas', [ZonaApiController::class, 'index'])->name('api.zonas.index');

        // ===== Fase 4.A — Catálogo de tipos de vehículo (solo lectura, Art. 25) =====
        Route::get('tipos-vehiculo', [ApiTipoVehiculoController::class, 'index'])->name('api.tipos-vehiculo.index');

        // ===== Fase 4.B — Vehículos del conductor (Art. 25) =====
        Route::apiResource('vehiculos', ApiVehiculoController::class)
            ->names([
                'index'   => 'api.vehiculos.index',     // url: /api/v1/vehiculos
                'store'   => 'api.vehiculos.store', // url: /api/v1/vehiculos
                'show'    => 'api.vehiculos.show',  // url: /api/v1/vehiculos/{vehiculo}
                'update'  => 'api.vehiculos.update',    // url: /api/v1/vehiculos/{vehiculo}
                'destroy' => 'api.vehiculos.destroy',   // url: /api/v1/vehiculos/{vehiculo}
            ]);
            

        // ===== Fase 4.C — Credencial CONADIS del conductor (Art. 26) =====
        Route::get('conductor/credencial',  [ApiCredencialDiscapacidadController::class, 'show'])->name('api.credencial.show');
        Route::post('conductor/credencial', [ApiCredencialDiscapacidadController::class, 'store'])->name('api.credencial.store');

        // ===== Fase 5.C — Tickets del conductor (Arts. 13, 14, 19, 22) =====
        // IMPORTANTE: rutas estáticas bajo /tickets ANTES de la ruta con parámetro {ticket}
        Route::get('tickets', [ApiTicketController::class, 'index'])
            ->middleware('permission:tickets.ver')
            ->name('api.tickets.index');
        Route::get('tickets/historial', [ApiTicketController::class, 'historial'])
            ->middleware('permission:tickets.ver')
            ->name('api.tickets.historial');

        // ===== Fase 5.D — Validación por placa (agente) — debe ir ANTES de tickets/{ticket} =====
        Route::get('tickets/validar/{placa}', [ValidacionTicketController::class, 'validar'])
            ->middleware('permission:sesiones_parqueo.ver')
            ->name('api.tickets.validar');

        // Rutas con parámetro {ticket} — van después de las estáticas
        Route::get('tickets/{ticket}', [ApiTicketController::class, 'show'])
            ->middleware('permission:tickets.ver')
            ->name('api.tickets.show');
        Route::post('tickets', [ApiTicketController::class, 'store'])
            ->middleware('permission:tickets.comprar')
            ->name('api.tickets.store');
        Route::post('tickets/{ticket}/cancelar', [ApiTicketController::class, 'cancelar'])
            ->middleware('permission:tickets.cancelar')
            ->name('api.tickets.cancelar');

        // ===== Fase 5.D — Sesiones de parqueo (Art. 38) =====
        Route::post('sesiones-parqueo', [ApiSesionParqueoController::class, 'store'])
            ->middleware('permission:sesiones_parqueo.iniciar')
            ->name('api.sesiones-parqueo.store');
        Route::get('sesiones-parqueo/{sesion}', [ApiSesionParqueoController::class, 'show'])
            ->middleware('permission:sesiones_parqueo.ver')
            ->name('api.sesiones-parqueo.show');

        // ===== Fase 7.C — Infracciones (agente en calle, Arts. 15, 17, 18, 28-30) =====
        // IMPORTANTE: rutas estáticas antes de la ruta con parámetro {infraccion}
        Route::post('infracciones', [ApiInfraccionController::class, 'store'])
            ->middleware('permission:infracciones.registrar')
            ->name('api.infracciones.store');
        Route::get('infracciones/{infraccion}', [ApiInfraccionController::class, 'show'])
            ->middleware('permission:infracciones.ver')
            ->name('api.infracciones.show');
        Route::post('infracciones/{infraccion}/inmovilizar', [ApiInfraccionController::class, 'inmovilizar'])
            ->middleware('permission:inmovilizaciones.aplicar')
            ->name('api.infracciones.inmovilizar');
        Route::post('infracciones/{infraccion}/liberar', [ApiInfraccionController::class, 'liberar'])
            ->middleware('permission:inmovilizaciones.retirar')
            ->name('api.infracciones.liberar');

        // ===== Fase 7.D — Infracciones conductor (historial + pago de multa) =====
        Route::get('conductor/infracciones', [ApiInfraccionController::class, 'historialConductor'])
            ->middleware('permission:infracciones.ver')
            ->name('api.conductor.infracciones.index');
        Route::post('infracciones/{infraccion}/pagar', [ApiInfraccionController::class, 'pagar'])
            ->middleware('permission:infracciones.ver')
            ->name('api.infracciones.pagar');

        // ===== Fase 9.5.4 — Fiscalización: Turnos, Recorridos, Incidentes (Art. 38) =====
        // IMPORTANTE: ruta estática /turnos/activo ANTES de la ruta con parámetro {turno}
        Route::get('turnos/activo', [ApiTurnoAgenteController::class, 'activo'])
            ->middleware('permission:turnos.ver')
            ->name('api.turnos.activo');
        Route::post('turnos', [ApiTurnoAgenteController::class, 'store'])
            ->middleware('permission:turnos.iniciar')
            ->name('api.turnos.store');
        Route::patch('turnos/{turno}/finalizar', [ApiTurnoAgenteController::class, 'finalizar'])
            ->middleware('permission:turnos.iniciar')
            ->name('api.turnos.finalizar');

        Route::post('recorridos', [ApiRecorridoAgenteController::class, 'store'])
            ->middleware('permission:turnos.iniciar')
            ->name('api.recorridos.store');

        Route::post('incidentes', [ApiIncidenteCalleController::class, 'store'])
            ->middleware('permission:incidentes.registrar')
            ->name('api.incidentes.store');

        // ===== Fase 9.5.5 — Comprobantes y Órdenes de Pago (Arts. 19, 28) =====
        Route::get('comprobantes/{comprobante}',      [App\Http\Controllers\Api\ComprobanteApiController::class, 'show'])->name('api.comprobantes.show');
        Route::get('comprobantes/{comprobante}/pdf',  [App\Http\Controllers\Api\ComprobanteApiController::class, 'pdf'])->name('api.comprobantes.pdf');
        Route::get('comprobantes/{comprobante}/link', [App\Http\Controllers\Api\ComprobanteApiController::class, 'generarLink'])->name('api.comprobantes.link');
        Route::post('ordenes-pago',                   [App\Http\Controllers\Api\OrdenPagoApiController::class,  'store'])->name('api.ordenes-pago.store');

        // ===== Fase 9.5.6 — Impugnaciones de infracciones (Art. 17.f) =====
        Route::post('infracciones/{infraccion}/impugnacion', [ApiImpugnacionController::class, 'store'])
            ->middleware('permission:impugnaciones.registrar')
            ->name('api.impugnaciones.store');
        Route::get('infracciones/{infraccion}/impugnacion', [ApiImpugnacionController::class, 'show'])
            ->middleware('permission:impugnaciones.ver')
            ->name('api.impugnaciones.show');

        // ===== Pago en efectivo OTP — conductor solicita código, agente confirma (Arts. 14, 19) =====
        Route::post('pagos/efectivo/solicitar', [App\Http\Controllers\Api\PagoEfectivoController::class, 'solicitar'])
            ->middleware('permission:pagos.ver')
            ->name('api.pagos.efectivo.solicitar');
        Route::post('pagos/efectivo/confirmar', [App\Http\Controllers\Api\PagoEfectivoController::class, 'confirmar'])
            ->middleware('permission:pagos.registrar')
            ->name('api.pagos.efectivo.confirmar');

        // ===== Fase 5.G — Dispositivos móviles FCM (placeholder; envío real en Fase 6) =====
        Route::post('dispositivos', [ApiDispositivoMovilController::class, 'store'])
            ->middleware('permission:dispositivos_moviles.registrar')
            ->name('api.dispositivos.store');
        Route::delete('dispositivos/{token}', [ApiDispositivoMovilController::class, 'destroy'])
            ->middleware('permission:dispositivos_moviles.registrar')
            ->name('api.dispositivos.destroy')
            ->where('token', '.+'); // El token FCM puede contener caracteres especiales
    });
});