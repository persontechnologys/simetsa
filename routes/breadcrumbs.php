<?php // routes/breadcrumbs.php

// Note: Laravel will automatically resolve `Breadcrumbs::` without
// this import. This is nice for IDE syntax and refactoring.
use Diglactic\Breadcrumbs\Breadcrumbs;

// This import is also not required, and you could replace `BreadcrumbTrail $trail`
//  with `$trail`. This is nice for IDE type checking and completion.
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Dashboard
Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Dashboard', route('dashboard'));
});

/* --------------------------------------- */
// Dashboard > Lista de Usuarios
Breadcrumbs::for('usuarios.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Lista de Usuarios', route('usuarios.index'));
});
// Dashboard > Crear Usuario
Breadcrumbs::for('usuarios.create', function (BreadcrumbTrail $trail) {
    $trail->parent('usuarios.index');
    $trail->push('Crear Usuario', route('usuarios.create'));
});
// Dashboard > editar Usuario
Breadcrumbs::for('usuarios.edit', function (BreadcrumbTrail $trail, $user) {
    $trail->parent('usuarios.index');
    $trail->push('Editar Usuario', route('usuarios.edit', $user->id));
});

// Dashboard > ver Usuario
Breadcrumbs::for('usuarios.show', function (BreadcrumbTrail $trail, $user) {
    $trail->parent('usuarios.index');
    $trail->push('Ver Usuario', route('usuarios.show', $user->id));
});

/* --------------------------------------- */

//dashboard > accesos
Breadcrumbs::for('accesos.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Accesos', route('accesos.index'));
});

/* --------------------------------------- */
// roles y permisos
Breadcrumbs::for('roles.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Roles y Permisos', route('roles.index'));
});
// crear rol
Breadcrumbs::for('roles.create', function (BreadcrumbTrail $trail) {
    $trail->parent('roles.index');
    $trail->push('Crear Rol', route('roles.create'));
});
// editar rol
Breadcrumbs::for('roles.edit', function (BreadcrumbTrail $trail, $role) {
    $trail->parent('roles.index');
    $trail->push('Editar Rol', route('roles.edit', $role->id)); 
});
// ver rol
Breadcrumbs::for('roles.show', function (BreadcrumbTrail $trail, $role) {
    $trail->parent('roles.index');
    $trail->push('Ver Rol', route('roles.show', $role->id)); 
});

/* --------------------------------------- */
// parametros listado
Breadcrumbs::for('parametros.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Parámetros del sistema', route('parametros.index'));
});
// editar parametro
Breadcrumbs::for('parametros.edit', function (BreadcrumbTrail $trail, $parametro) {
    $trail->parent('parametros.index');
    $trail->push('Editar Parámetro', route('parametros.edit', $parametro->id)); 
});

/* --------------------------------------- */
// tipos de plaza listado
Breadcrumbs::for('tipos-plaza.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Tipos de plaza', route('tipos-plaza.index')); 
});
// crear tipo de plaza
Breadcrumbs::for('tipos-plaza.create', function (BreadcrumbTrail $trail) {
    $trail->parent('tipos-plaza.index');
    $trail->push('Crear tipo de plaza', route('tipos-plaza.create')); 
});
// editar tipo de plaza
Breadcrumbs::for('tipos-plaza.edit', function (BreadcrumbTrail $trail, $tipoPlaza) {
    $trail->parent('tipos-plaza.index');
    $trail->push('Editar tipo de plaza', route('tipos-plaza.edit', $tipoPlaza->id)); 
});

/* --------------------------------------- */
// horarios de operación listado
Breadcrumbs::for('horarios-operacion.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Horarios de operación', route('horarios-operacion.index')); 
});
// editar horario de operación
Breadcrumbs::for('horarios-operacion.edit', function (BreadcrumbTrail $trail, $horarioOperacion) {
    $trail->parent('horarios-operacion.index');     
    $trail->push('Editar horario de operación', route('horarios-operacion.edit', $horarioOperacion->id)); 
});

/* --------------------------------------- */
//dias feriados listado
Breadcrumbs::for('dias-feriado.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Días feriado', route('dias-feriado.index'));  
});
// editar dia feriado
Breadcrumbs::for('dias-feriado.edit', function (BreadcrumbTrail $trail, $diaFeriado) {
    $trail->parent('dias-feriado.index');
    $trail->push('Editar día feriado', route('dias-feriado.edit', $diaFeriado->id)); 
});
// crear dia feriado
Breadcrumbs::for('dias-feriado.create', function (BreadcrumbTrail $trail) {
    $trail->parent('dias-feriado.index');
    $trail->push('Crear día feriado', route('dias-feriado.create')); 
});

/* --------------------------------------- */
// tarifas listado
Breadcrumbs::for('tarifas.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Tarifas', route('tarifas.index')); 
}); 
// crear tarifa
Breadcrumbs::for('tarifas.create', function (BreadcrumbTrail $trail) {
    $trail->parent('tarifas.index');
    $trail->push('Crear tarifa', route('tarifas.create'));
});
// editar tarifa
Breadcrumbs::for('tarifas.edit', function (BreadcrumbTrail $trail, $tarifa) {
    $trail->parent('tarifas.index');        
    $trail->push('Editar tarifa', route('tarifas.edit', $tarifa->id)); 
});

/* --------------------------------------- */
// zonas listado
Breadcrumbs::for('zonas.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Zonas tarifadas', route('zonas.index')); 
});
// editar zona
Breadcrumbs::for('zonas.edit', function (BreadcrumbTrail $trail, $zona) {
    $trail->parent('zonas.index');      
    $trail->push('Editar zona', route('zonas.edit', $zona->id)); 
});
// crear zona  
Breadcrumbs::for('zonas.create', function (BreadcrumbTrail $trail) {
    $trail->parent('zonas.index');      
    $trail->push('Crear zona', route('zonas.create')); 
});

/* --------------------------------------- */
// calles listado
Breadcrumbs::for('calles.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Calles', route('calles.index'));
});
// crear calle
Breadcrumbs::for('calles.create', function (BreadcrumbTrail $trail) {
    $trail->parent('calles.index');
    $trail->push('Crear calle', route('calles.create'));
});
// editar calle     
Breadcrumbs::for('calles.edit', function (BreadcrumbTrail $trail, $calle) {
    $trail->parent('calles.index');
    $trail->push('Editar calle', route('calles.edit', $calle->id));
});
/* --------------------------------------- */
// manzanas listado
Breadcrumbs::for('manzanas.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Manzanas', route('manzanas.index'));
});
// crear manzana
Breadcrumbs::for('manzanas.create', function (BreadcrumbTrail $trail) {
    $trail->parent('manzanas.index');   
    $trail->push('Crear manzana', route('manzanas.create'));
});
// editar manzana
Breadcrumbs::for('manzanas.edit', function (BreadcrumbTrail $trail, $manzana) {
    $trail->parent('manzanas.index');   
    $trail->push('Editar manzana', route('manzanas.edit', $manzana->id));
});

/* --------------------------------------- */
// plazas listado
Breadcrumbs::for('plazas.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Plazas', route('plazas.index'));
});
// crear plaza
Breadcrumbs::for('plazas.create', function (BreadcrumbTrail $trail) {   
    $trail->parent('plazas.index');
    $trail->push('Crear plaza', route('plazas.create'));
});
// editar plaza 
Breadcrumbs::for('plazas.edit', function (BreadcrumbTrail $trail, $plaza) {   
    $trail->parent('plazas.index');
    $trail->push('Editar plaza', route('plazas.edit', $plaza->id));
});

/* --------------------------------------- */
// Solicitudes de agentes
Breadcrumbs::for('solicitudes-agente.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Solicitudes de agentes', route('solicitudes-agente.index'));
});
// ver solicitud de agente
Breadcrumbs::for('solicitudes-agente.show', function (BreadcrumbTrail $trail, $solicitudAgente) {
    $trail->parent('solicitudes-agente.index');
    $trail->push('Ver solicitud de agente', route('solicitudes-agente.show', $solicitudAgente->id));
});
// editar solicitud de agente
Breadcrumbs::for('solicitudes-agente.edit', function (BreadcrumbTrail $trail, $solicitudAgente) {
    $trail->parent('solicitudes-agente.index');
    $trail->push('Editar solicitud de agente', route('solicitudes-agente.edit', $solicitudAgente->id));
});

/* --------------------------------------- */
/* Cursos de capacitación */
Breadcrumbs::for('cursos-capacitacion.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Cursos de capacitación', route('cursos-capacitacion.index'));
});
// crear curso de capacitación
Breadcrumbs::for('cursos-capacitacion.create', function (BreadcrumbTrail $trail) {
    $trail->parent('cursos-capacitacion.index');
    $trail->push('Crear curso de capacitación', route('cursos-capacitacion.create'));
});
// editar curso de capacitación
Breadcrumbs::for('cursos-capacitacion.edit', function (BreadcrumbTrail $trail, $cursoCapacitacion) {
    $trail->parent('cursos-capacitacion.index');
    $trail->push('Editar curso de capacitación', route('cursos-capacitacion.edit', $cursoCapacitacion->id));
});
// ver curso de capacitación
Breadcrumbs::for('cursos-capacitacion.show', function (BreadcrumbTrail $trail, $cursoCapacitacion) {
    $trail->parent('cursos-capacitacion.index');    
    $trail->push('Ver curso de capacitación', route('cursos-capacitacion.show', $cursoCapacitacion->id));
});

/* --------------------------------------- */
/* Solicitud punto de venta */
// ===== Fase 4.A — Tipos de vehículo =====
Breadcrumbs::for('tipos-vehiculo.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Tipos de vehículo', route('tipos-vehiculo.index'));
});
Breadcrumbs::for('tipos-vehiculo.create', function (BreadcrumbTrail $trail) {
    $trail->parent('tipos-vehiculo.index');
    $trail->push('Crear tipo de vehículo', route('tipos-vehiculo.create'));
});
Breadcrumbs::for('tipos-vehiculo.edit', function (BreadcrumbTrail $trail, $tipoVehiculo) {
    $trail->parent('tipos-vehiculo.index');
    $trail->push('Editar tipo de vehículo', route('tipos-vehiculo.edit', $tipoVehiculo->id));
});

// ===== Fase 4.B — Vehículos (supervisión backoffice) =====
Breadcrumbs::for('vehiculos.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Vehículos', route('vehiculos.index'));
});
Breadcrumbs::for('vehiculos.show', function (BreadcrumbTrail $trail, $vehiculo) {
    $trail->parent('vehiculos.index');
    $trail->push($vehiculo->placa, route('vehiculos.show', $vehiculo->id));
});

Breadcrumbs::for('solicitudes-punto-venta.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Solicitudes de punto de venta', route('solicitudes-punto-venta.index'));
});
// ver solicitud de punto de venta
Breadcrumbs::for('solicitudes-punto-venta.show', function (BreadcrumbTrail $trail, $solicitudPuntoVenta) {
    $trail->parent('solicitudes-punto-venta.index');
    $trail->push('Ver solicitud de punto de venta', route('solicitudes-punto-venta.show', $solicitudPuntoVenta->id));
});
// editar solicitud de punto de venta
Breadcrumbs::for('solicitudes-punto-venta.edit', function (BreadcrumbTrail $trail, $solicitudPuntoVenta) {
    $trail->parent('solicitudes-punto-venta.index');
    $trail->push('Editar solicitud de punto de venta', route    ('solicitudes-punto-venta.edit', $solicitudPuntoVenta->id));
});
// crear solicitud de punto de venta
Breadcrumbs::for('solicitudes-punto-venta.create', function (BreadcrumbTrail $trail) {
    $trail->parent('solicitudes-punto-venta.index');
    $trail->push('Crear solicitud de punto de venta', route('solicitudes-punto-venta.create'));
});


// ===== Fase 4.D — Conductores backoffice =====
Breadcrumbs::for('conductores.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Conductores', route('conductores.index'));
});
Breadcrumbs::for('conductores.show', function (BreadcrumbTrail $trail, $conductor) {
    $trail->parent('conductores.index');
    $trail->push($conductor->user?->name ?? $conductor->codigo, route('conductores.show', $conductor));
});

// ===== Fase 4.C — Credenciales CONADIS backoffice =====
Breadcrumbs::for('credenciales-discapacidad.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Credenciales CONADIS', route('credenciales-discapacidad.index'));
});

// ===== Fase 4.D — Vehículos exonerados =====
Breadcrumbs::for('vehiculos-exonerados.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Vehículos exonerados', route('vehiculos-exonerados.index'));
});
Breadcrumbs::for('vehiculos-exonerados.create', function (BreadcrumbTrail $trail) {
    $trail->parent('vehiculos-exonerados.index');
    $trail->push('Registrar', route('vehiculos-exonerados.create'));
});
Breadcrumbs::for('vehiculos-exonerados.edit', function (BreadcrumbTrail $trail, $vehiculo) {
    $trail->parent('vehiculos-exonerados.index');
    $trail->push('Editar ' . $vehiculo->placa, route('vehiculos-exonerados.edit', $vehiculo));
});

// ===== Fase 5 — Tickets digitales =====
Breadcrumbs::for('tickets.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Tickets', route('tickets.index'));
});
Breadcrumbs::for('tickets.show', function (BreadcrumbTrail $trail, $ticket) {
    $trail->parent('tickets.index');
    $trail->push($ticket->codigo, route('tickets.show', $ticket));
});

// ===== Fase 7 — Infracciones =====
Breadcrumbs::for('infracciones.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Infracciones', route('infracciones.index'));
});
Breadcrumbs::for('infracciones.show', function (BreadcrumbTrail $trail, $infraccion) {
    $trail->parent('infracciones.index');
    $trail->push("Infracción #{$infraccion->id}", route('infracciones.show', $infraccion));
});

// ===== Fase 9.5.3 — Cancelaciones =====
Breadcrumbs::for('cancelaciones.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Cancelaciones', route('cancelaciones.index'));
});
Breadcrumbs::for('cancelaciones.show', function (BreadcrumbTrail $trail, $cancelacion) {
    $trail->parent('cancelaciones.index');
    $trail->push("Cancelación #{$cancelacion->id}", route('cancelaciones.show', $cancelacion));
});

// ===== Fase 9.5.3 — Sesiones de Parqueo =====
Breadcrumbs::for('sesiones-parqueo.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Sesiones de Parqueo', route('sesiones-parqueo.index'));
});

// ===== Fase 9.5.3 — Inmovilizaciones =====
Breadcrumbs::for('inmovilizaciones.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Inmovilizaciones', route('inmovilizaciones.index'));
});
Breadcrumbs::for('inmovilizaciones.show', function (BreadcrumbTrail $trail, $inmovilizacion) {
    $trail->parent('inmovilizaciones.index');
    $trail->push("Inmovilización #{$inmovilizacion->id}", route('inmovilizaciones.show', $inmovilizacion));
});

// ===== Fase 9.5.3 — Transacciones de Pago =====
Breadcrumbs::for('transacciones.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Transacciones de Pago', route('transacciones.index'));
});

// ===== Fase 9.5.4 — Fiscalización: Turnos =====
Breadcrumbs::for('turnos.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Turnos de Agentes', route('turnos.index'));
});
Breadcrumbs::for('turnos.show', function (BreadcrumbTrail $trail, $turno) {
    $trail->parent('turnos.index');
    $trail->push('Turno #' . $turno->id, route('turnos.show', $turno));
});

// ===== Fase 9.5.5 — Órdenes de Pago, Comprobantes, Liquidaciones =====
Breadcrumbs::for('ordenes-pago.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Órdenes de Pago', route('ordenes-pago.index'));
});
Breadcrumbs::for('ordenes-pago.show', function (BreadcrumbTrail $trail, $ordenPago) {
    $trail->parent('ordenes-pago.index');
    $trail->push($ordenPago->numero_orden, route('ordenes-pago.show', $ordenPago));
});
Breadcrumbs::for('comprobantes.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Comprobantes', route('comprobantes.index'));
});
Breadcrumbs::for('liquidaciones.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Liquidaciones', route('liquidaciones.index'));
});

// ===== Impugnaciones (Fase 9.5.6) =====
Breadcrumbs::for('impugnaciones.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Impugnaciones', route('impugnaciones.index'));
});
Breadcrumbs::for('impugnaciones.show', function (BreadcrumbTrail $trail, $impugnacion) {
    $trail->parent('impugnaciones.index');
    $trail->push("Impugnación #{$impugnacion->id}", route('impugnaciones.show', $impugnacion->id));
});

// ===== Reportes (Fase 8) =====
Breadcrumbs::for('reportes.recaudacion', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Recaudación', route('reportes.recaudacion.index'));
});
Breadcrumbs::for('reportes.infracciones', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Infracciones', route('reportes.infracciones.index'));
});
Breadcrumbs::for('reportes.ocupacion', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Ocupación', route('reportes.ocupacion.index'));
});
