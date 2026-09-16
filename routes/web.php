<?php
// routes/web.php

use App\Http\Controllers\AgenteParqueoController;
use App\Http\Controllers\AmonestacionAgenteController;
use App\Http\Controllers\AsignacionZonaController;
use App\Http\Controllers\CalleController;
use App\Http\Controllers\CursoCapacitacionController;
use App\Http\Controllers\DiaFeriadoController;
use App\Http\Controllers\DocumentoAgenteController;
use App\Http\Controllers\DocumentoPuntoVentaController;
use App\Http\Controllers\HorarioOperacionController;
use App\Http\Controllers\HorarioRotativoController;
use App\Http\Controllers\InscripcionCursoController;
use App\Http\Controllers\ManzanaController;
use App\Http\Controllers\ParametroController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PlazaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PuntoVentaController;
use App\Http\Controllers\RegistroAccesoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SolicitudAgenteController;
use App\Http\Controllers\SolicitudPuntoVentaController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\TipoPlazaController;
use App\Http\Controllers\ConductorController;
use App\Http\Controllers\InfraccionController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\CredencialDiscapacidadController;
use App\Http\Controllers\TipoVehiculoController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\VehiculoExoneradoController;
use App\Http\Controllers\CancelacionController;
use App\Http\Controllers\InmovilizacionWebController;
use App\Http\Controllers\SesionParqueoWebController;
use App\Http\Controllers\TransaccionPagoWebController;
use App\Http\Controllers\TurnoAgenteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ImpugnacionController;
use App\Http\Controllers\OrdenPagoController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\LiquidacionController;
use App\Http\Controllers\Reportes\DashboardController;
use App\Http\Controllers\Reportes\InfraccionesController;
use App\Http\Controllers\Reportes\OcupacionController;
use App\Http\Controllers\Reportes\RecaudacionController;
use App\Http\Controllers\ZonaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/kpis', [DashboardController::class, 'kpis'])->name('dashboard.kpis');
});

/*
 * Rutas autenticadas.
 *
 * Estructura:
 *  - Bloque 1: rutas que se acceden SIN tener perfil completo
 *    (la pantalla de auto-completar y el profile de Breeze para name/email/password).
 *  - Bloque 2: rutas operativas del SIMETSA, todas requieren perfil completo
 *    (middleware `perfil.completo` definido en 1.D).
 */
Route::middleware('auth')->group(function () {

    // ===== Bloque 1: accesible sin perfil completo =====

    // Auto-gestión del PerfilUsuario (SIMETSA)
    Route::get('/perfil',   [PerfilController::class, 'mostrar'])->name('perfil.completar');
    Route::patch('/perfil', [PerfilController::class, 'actualizar'])->name('perfil.actualizar');

    // Profile de Breeze (name, email, password del User)
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    // ===== Bloque 2: requieren perfil completo + consentimiento LOPDP =====
    Route::middleware('perfil.completo')->group(function () {

        // Gestión de usuarios (Fase 1.E.2)
        Route::resource('usuarios', UsuarioController::class)
            ->parameters(['usuarios' => 'usuario']);
        Route::patch('usuarios/{usuario}/reactivar', [UsuarioController::class, 'reactivar'])
            ->name('usuarios.reactivar');

        // Gestión de roles (Fase 1.E.3)
        Route::resource('roles', RolController::class)
            ->parameters(['roles' => 'rol']);

        // ===== SIMETSA - Registro de accesos (Fase 1.F) =====
        Route::get('/accesos', [RegistroAccesoController::class, 'index'])
            ->name('accesos.index')
            ->middleware('permission:accesos.ver');
        
        // ===== SIMETSA - Parámetros del sistema (Fase 2.A) =====
        Route::get('/parametros',
            [ParametroController::class, 'index'])
            ->name('parametros.index')
            ->middleware('permission:parametros.ver');

        Route::get('/parametros/{parametro}/edit',
            [ParametroController::class, 'edit'])
            ->name('parametros.edit')
            ->middleware('permission:parametros.editar');

        Route::put('/parametros/{parametro}',
            [ParametroController::class, 'update'])
            ->name('parametros.update')
            ->middleware('permission:parametros.editar');

        // ===== SIMETSA - Catálogos simples (Fase 2.B) =====

        // Tipos de plaza
        Route::resource('tipos-plaza', TipoPlazaController::class)
            ->parameters(['tipos-plaza' => 'tipo_plaza'])
            ->except(['show']);

        // Días feriado
        Route::resource('dias-feriado', DiaFeriadoController::class)
            ->parameters(['dias-feriado' => 'dia_feriado'])
            ->except(['show']);

        // Horarios de operación (solo index + edit + update)
        Route::get('/horarios-operacion',
            [HorarioOperacionController::class, 'index'])
            ->name('horarios-operacion.index');
        Route::get('/horarios-operacion/{horario_operacion}/edit',
            [HorarioOperacionController::class, 'edit'])
            ->name('horarios-operacion.edit');
        Route::put('/horarios-operacion/{horario_operacion}',
            [HorarioOperacionController::class, 'update'])
            ->name('horarios-operacion.update');

        // ===== SIMETSA - Tarifas (Fase 2.C) =====
        Route::resource('tarifas', TarifaController::class)
            ->parameters(['tarifas' => 'tarifa'])
            ->except(['show']);
        Route::patch('tarifas/{tarifa}/reactivar', [TarifaController::class, 'reactivar'])
            ->name('tarifas.reactivar')
            ->withTrashed();
        // ===== SIMETSA - Zonas tarifadas (Fase 2.D.1) =====
        Route::resource('zonas', ZonaController::class)
            ->parameters(['zonas' => 'zona'])
            ->except(['show']);

        // ===== SIMETSA - Calles tarifadas (Fase 2.D.2) =====
        Route::resource('calles', CalleController::class)
            ->parameters(['calles' => 'calle'])
            ->except(['show']);

        // ===== SIMETSA - Manzanas (Fase 2.D.3) =====
        Route::resource('manzanas', ManzanaController::class)
            ->parameters(['manzanas' => 'manzana'])
            ->except(['show']);
        // ===== SIMETSA - Plazas de estacionamiento (Fase 2.E) =====
        Route::resource('plazas', PlazaController::class)
            ->parameters(['plazas' => 'plaza'])
            ->except(['show']);
        
        // ===== SIMETSA - Solicitudes de Agente (Fase 3.A) =====
        Route::resource('solicitudes-agente', SolicitudAgenteController::class)
            ->parameters(['solicitudes-agente' => 'solicitud']);

        Route::post('solicitudes-agente/{solicitud}/aprobar-documentacion',
            [SolicitudAgenteController::class, 'aprobarDocumentacion'])
            ->name('solicitudes-agente.aprobar-documentacion');

        Route::post('solicitudes-agente/{solicitud}/rechazar',
            [SolicitudAgenteController::class, 'rechazar'])
            ->name('solicitudes-agente.rechazar');

        // Documentos de la solicitud
        Route::post('solicitudes-agente/{solicitud}/documentos',
            [DocumentoAgenteController::class, 'store'])
            ->name('documentos-agente.store');
    
        Route::patch('documentos-agente/{documento}/validar',
            [DocumentoAgenteController::class, 'validar'])
            ->name('documentos-agente.validar');

        Route::delete('documentos-agente/{documento}',
            [DocumentoAgenteController::class, 'destroy'])
            ->name('documentos-agente.destroy');

        Route::get('documentos-agente/{documento}/descargar',
            [DocumentoAgenteController::class, 'descargar'])
            ->name('documentos-agente.descargar');

        // ===== SIMETSA - Cursos de Capacitación (Fase 3.B) =====
        Route::resource('cursos-capacitacion', CursoCapacitacionController::class)
            ->parameters(['cursos-capacitacion' => 'curso']);

        Route::post('cursos-capacitacion/{curso}/inscripciones',
            [InscripcionCursoController::class, 'store'])
            ->name('inscripciones-curso.store');

        Route::post('inscripciones-curso/{inscripcion}/calificar',
            [InscripcionCursoController::class, 'calificar'])
            ->name('inscripciones-curso.calificar');

        Route::delete('inscripciones-curso/{inscripcion}',
            [InscripcionCursoController::class, 'destroy'])
            ->name('inscripciones-curso.destroy');

        // ===== SIMETSA - Agentes de Parqueo (Fase 3.C) =====
        Route::resource('agentes-parqueo', AgenteParqueoController::class)
            ->parameters(['agentes-parqueo' => 'agente'])
            ->only(['index', 'show']);

        Route::post('solicitudes-agente/{solicitud}/autorizar',
            [AgenteParqueoController::class, 'autorizar'])
            ->name('agentes-parqueo.autorizar');

        Route::patch('agentes-parqueo/{agente}/expediente',
            [AgenteParqueoController::class, 'actualizarExpediente'])
            ->name('agentes-parqueo.expediente');

        Route::patch('agentes-parqueo/{agente}/estado',
            [AgenteParqueoController::class, 'cambiarEstado'])
            ->name('agentes-parqueo.estado');

        // ===== SIMETSA - Operación del agente (Fase 3.D) =====
        // Asignaciones de zona (Art. 16)
        Route::post('agentes-parqueo/{agente}/asignaciones',
            [AsignacionZonaController::class, 'store'])->name('asignaciones-zona.store');
        Route::delete('asignaciones-zona/{asignacion}',
            [AsignacionZonaController::class, 'destroy'])->name('asignaciones-zona.destroy');
        Route::patch('asignaciones-zona/{asignacion}',
            [AsignacionZonaController::class, 'update'])->name('asignaciones-zona.update');


        // Horarios rotativos (Art. 37.4)
        Route::post('agentes-parqueo/{agente}/horarios',
            [HorarioRotativoController::class, 'store'])->name('horarios-rotativos.store');
        Route::delete('horarios-rotativos/{horario}',
            [HorarioRotativoController::class, 'destroy'])->name('horarios-rotativos.destroy');
        Route::patch('horarios-rotativos/{horario}',
            [HorarioRotativoController::class, 'update'])->name('horarios-rotativos.update');


        // Amonestaciones (Art. 40)
        Route::post('agentes-parqueo/{agente}/amonestaciones',
            [AmonestacionAgenteController::class, 'store'])->name('amonestaciones-agente.store');
        Route::delete('amonestaciones-agente/{amonestacion}',
            [AmonestacionAgenteController::class, 'destroy'])->name('amonestaciones-agente.destroy');
        Route::patch('amonestaciones-agente/{amonestacion}',
            [AmonestacionAgenteController::class, 'update'])->name('amonestaciones-agente.update');
        
            // ===== Fase 3.E.1 — Solicitudes de punto de venta (Art. 31) =====
        Route::resource('solicitudes-punto-venta', SolicitudPuntoVentaController::class)
            ->parameters(['solicitudes-punto-venta' => 'solicitud']);

        Route::post('solicitudes-punto-venta/{solicitud}/aprobar-documentacion',
            [SolicitudPuntoVentaController::class, 'aprobarDocumentacion'])
            ->name('solicitudes-punto-venta.aprobar-documentacion');

        Route::post('solicitudes-punto-venta/{solicitud}/rechazar',
            [SolicitudPuntoVentaController::class, 'rechazar'])
            ->name('solicitudes-punto-venta.rechazar');

        Route::post('solicitudes-punto-venta/{solicitud}/documentos',
            [DocumentoPuntoVentaController::class, 'store'])
            ->name('documentos-punto-venta.store');

        Route::patch('documentos-punto-venta/{documento}/validar',
            [DocumentoPuntoVentaController::class, 'validar'])
            ->name('documentos-punto-venta.validar');

        Route::delete('documentos-punto-venta/{documento}',
            [DocumentoPuntoVentaController::class, 'destroy'])
            ->name('documentos-punto-venta.destroy');

        Route::get('documentos-punto-venta/{documento}/descargar',
            [DocumentoPuntoVentaController::class, 'descargar'])
            ->name('documentos-punto-venta.descargar');

        // ===== Fase 3.E.2 — Puntos de venta activos =====
        Route::resource('puntos-venta', PuntoVentaController::class)
            ->only(['index', 'show'])
            ->parameters(['puntos-venta' => 'punto']);

        Route::post('solicitudes-punto-venta/{solicitud}/activar',
            [PuntoVentaController::class, 'activar'])->name('puntos-venta.activar');

        Route::patch('puntos-venta/{punto}/estado',
            [PuntoVentaController::class, 'cambiarEstado'])->name('puntos-venta.estado');

            
        // ===== SIMETSA - Catálogo de tipos de vehículo (Fase 4.A, Art. 25) =====
        Route::resource('tipos-vehiculo', TipoVehiculoController::class)
            ->parameters(['tipos-vehiculo' => 'tipo_vehiculo'])
            ->except(['show']);

        // ===== SIMETSA - Supervisión de vehículos (Fase 4.B, Art. 25) =====
        Route::resource('vehiculos', VehiculoController::class)
            ->only(['index', 'show']);
        Route::patch('vehiculos/{vehiculo}/estado',
            [VehiculoController::class, 'cambiarEstado'])->name('vehiculos.estado');

        
        // ===== SIMETSA — Credenciales CONADIS backoffice (Fase 4.C, Art. 26) =====
        Route::get('credenciales-discapacidad', [CredencialDiscapacidadController::class, 'index'])
            ->name('credenciales-discapacidad.index');
        Route::patch('credenciales-discapacidad/{credencial_discapacidad}/aprobar',
            [CredencialDiscapacidadController::class, 'aprobar'])->name('credenciales-discapacidad.aprobar');
        Route::patch('credenciales-discapacidad/{credencial_discapacidad}/rechazar',
            [CredencialDiscapacidadController::class, 'rechazar'])->name('credenciales-discapacidad.rechazar');

        // ===== SIMETSA — Conductores backoffice (Fase 4.D, Art. 37) =====
        Route::resource('conductores', ConductorController::class)
            ->only(['index', 'show'])
            ->parameters(['conductores' => 'conductor']);
        Route::patch('conductores/{conductor}/bloquear',
            [ConductorController::class, 'bloquear'])->name('conductores.bloquear');
        Route::patch('conductores/{conductor}/desbloquear',
            [ConductorController::class, 'desbloquear'])->name('conductores.desbloquear');
            

        // ===== SIMETSA — Vehículos exonerados (Fase 4.D, Art. 27) =====
        Route::patch('vehiculos-exonerados/{vehiculo_exonerado}/toggle', [VehiculoExoneradoController::class, 'toggle'])
            ->name('vehiculos-exonerados.toggle');
        Route::resource('vehiculos-exonerados', VehiculoExoneradoController::class)
            ->parameters(['vehiculos-exonerados' => 'vehiculo_exonerado'])
            ->except(['show']);

        // ===== SIMETSA — Tickets digitales supervisión (Fase 5.E-5.F) =====
        Route::resource('tickets', TicketController::class)
            ->only(['index', 'show'])
            ->parameters(['tickets' => 'ticket']);
        Route::patch('tickets/{ticket}/anular', [TicketController::class, 'anular'])
            ->name('tickets.anular');

        // ===== SIMETSA — Infracciones supervisión (Fase 7.E, Arts. 15, 28-30) =====
        Route::resource('infracciones', InfraccionController::class)
            ->only(['index', 'show'])
            ->parameters(['infracciones' => 'infraccion']);
        Route::patch('infracciones/{infraccion}/anular', [InfraccionController::class, 'anular'])
            ->name('infracciones.anular');

        // ===== SIMETSA — Cancelaciones supervisión (Fase 9.5.3) =====
        Route::resource('cancelaciones', CancelacionController::class)
            ->only(['index', 'show'])
            ->parameters(['cancelaciones' => 'cancelacion']);

        // ===== SIMETSA — Sesiones de Parqueo supervisión (Fase 9.5.3) =====
        Route::get('sesiones-parqueo', [SesionParqueoWebController::class, 'index'])
            ->name('sesiones-parqueo.index');

        // ===== SIMETSA — Inmovilizaciones supervisión (Fase 9.5.3, Art. 15) =====
        Route::resource('inmovilizaciones', InmovilizacionWebController::class)
            ->only(['index', 'show'])
            ->parameters(['inmovilizaciones' => 'inmovilizacion']);
        Route::patch('inmovilizaciones/{inmovilizacion}/liberar', [InmovilizacionWebController::class, 'liberar'])
            ->name('inmovilizaciones.liberar');

        // ===== SIMETSA — Transacciones de Pago supervisión (Fase 9.5.3, Art. 21) =====
        Route::get('transacciones', [TransaccionPagoWebController::class, 'index'])
            ->name('transacciones.index');

        // ===== SIMETSA — Fiscalización: Turnos (Fase 9.5.4, Art. 38) =====
        Route::resource('turnos', TurnoAgenteController::class)
            ->only(['index', 'show'])
            ->parameters(['turnos' => 'turno']);

        // ===== SIMETSA — Impugnaciones (Fase 9.5.6, Art. 17.f) =====
        Route::resource('impugnaciones', ImpugnacionController::class)
            ->only(['index', 'show'])
            ->parameters(['impugnaciones' => 'impugnacion']);
        Route::patch('impugnaciones/{impugnacion}/admitir',  [ImpugnacionController::class, 'admitir'])
            ->name('impugnaciones.admitir');
        Route::patch('impugnaciones/{impugnacion}/rechazar', [ImpugnacionController::class, 'rechazar'])
            ->name('impugnaciones.rechazar');
        Route::patch('impugnaciones/{impugnacion}/resolver', [ImpugnacionController::class, 'resolver'])
            ->name('impugnaciones.resolver');

        // ===== SIMETSA — Órdenes de Pago (Fase 9.5.5, Art. 28) =====
        Route::resource('ordenes-pago', OrdenPagoController::class)
            ->only(['index', 'show'])
            ->parameters(['ordenes-pago' => 'ordenPago']);
        Route::patch('ordenes-pago/{ordenPago}/anular', [OrdenPagoController::class, 'anular'])
            ->name('ordenes-pago.anular');

        // ===== SIMETSA — Comprobantes supervisión (Fase 9.5.5, Art. 19) =====
        Route::get('comprobantes', [ComprobanteController::class, 'index'])->name('comprobantes.index');

        // ===== SIMETSA — Liquidaciones (Fase 9.5.5, Art. 21) =====
        Route::get('liquidaciones', [LiquidacionController::class, 'index'])->name('liquidaciones.index');

        // ===== SIMETSA — Reportes (Fase 8) =====
        Route::prefix('reportes')->name('reportes.')->group(function () {

            // 8.B — Recaudación
            Route::get('recaudacion',       [RecaudacionController::class, 'index'])->name('recaudacion.index');
            Route::get('recaudacion/excel', [RecaudacionController::class, 'excel'])->name('recaudacion.excel');
            Route::get('recaudacion/pdf',   [RecaudacionController::class, 'pdf'])->name('recaudacion.pdf');

            // 8.C — Infracciones
            Route::get('infracciones',       [InfraccionesController::class, 'index'])->name('infracciones.index');
            Route::get('infracciones/excel', [InfraccionesController::class, 'excel'])->name('infracciones.excel');
            Route::get('infracciones/pdf',   [InfraccionesController::class, 'pdf'])->name('infracciones.pdf');

            // 8.D — Ocupación
            Route::get('ocupacion', [OcupacionController::class, 'index'])->name('ocupacion.index');

        });

    });
});

require __DIR__.'/auth.php';