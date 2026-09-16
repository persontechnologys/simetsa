# Backlog — Cierre de Módulos Críticos pre-Fase 10

> Generado: 2026-06-06 | Auditoría base: `docs/auditoria-fase-9-5-pre-fase10.md`  
> Leyenda: 🔴 Crítico · 🟠 Alto · 🟡 Medio · 🟢 Bajo

---

## Resumen de brechas por prioridad

| Prioridad | Cantidad |
|-----------|----------|
| 🔴 Crítico | 6 |
| 🟠 Alto | 9 |
| 🟡 Medio | 5 |
| 🟢 Bajo | 4 |

---

## Tabla de brechas

### CRÍTICAS — Bloquean testing interno

| # | Módulo | Funcionalidad | Estado actual | Brecha detectada | Prioridad | Archivos involucrados | Acción recomendada | Criterio de aceptación | Dependencias | Riesgo si no se corrige | Fase |
|---|--------|---------------|---------------|-----------------|-----------|----------------------|--------------------|------------------------|--------------|------------------------|------|
| 1 | App Móvil | Redirección usuario multi-rol | Bug activo | `app/index.js:21-24` usa if-else secuencial; conductor siempre gana sobre agente | 🔴 | `app/index.js`, `src/context/AuthContext.js` | Reemplazar if-else por lógica con `activeRole`; agregar pantalla `selector-rol.js` | Usuario conductor+agente puede elegir y cambiar modo sin logout | Ninguna | Usuario operativo real atrapado en modo conductor; las funciones de agente son inaccesibles | 9.5.2 |
| 2 | App Móvil | Selector de rol activo | No existe | No hay pantalla ni estado `activeRole` en el contexto | 🔴 | `src/context/AuthContext.js`, `app/selector-rol.js` (crear) | Agregar `activeRole`/`setActiveRole` al AuthContext + pantalla selector | Pantalla aparece cuando `roles.length > 1`; persiste en SecureStore | #1 | Sin selector el usuario no puede elegir su modo operativo | 9.5.2 |
| 3 | App Móvil | Cambio de modo desde perfil | No existe | Pantallas de perfil solo tienen "Cerrar Sesión" | 🔴 | `app/(conductor)/(tabs)/perfil.js`, `app/(agente)/(tabs)/perfil.js` | Agregar botón condicional "Cambiar a modo [otro rol]" | Botón solo visible si usuario tiene ambos roles; al presionar redirige sin logout | #2 | Usuario no puede cambiar de modo en campo; debe cerrar sesión | 9.5.2 |
| 4 | Backoffice | Vista Inmovilizaciones activas | No existe | Solo embebida en `infracciones/show`; sin listado independiente | 🔴 | `routes/web.php`, controlador web (crear o extender `InfraccionController`), `resources/views/inmovilizaciones/index.blade.php` | Crear ruta `GET /inmovilizaciones` y vista con filtro por estado activo | Comisario puede ver listado de vehículos inmovilizados con botón Liberar | Ninguna | El comisario no puede gestionar inmovilizaciones activas desde el backoffice | 9.5.3 |
| 5 | Backoffice | Vista Cancelaciones | No existe | Modelo y BD existen; sin ruta ni vista web | 🔴 | `routes/web.php`, `app/Http/Controllers/CancelacionController.php` (crear), `resources/views/cancelaciones/index.blade.php` | Crear controller web simple (solo index + show) y vistas de solo lectura | Comisario puede ver historial de cancelaciones con filtros básicos | Ninguna | Sin auditoría de cancelaciones en el backoffice | 9.5.3 |
| 6 | Backend | Tests de Cancelaciones | 0 tests | Modelo, migración y servicio existen; 0 tests | 🔴 | `tests/Feature/CancelacionTest.php` (crear) | Crear tests para: crear cancelación via API, listar, verificar estado ticket tras cancelar | Tests pasan en CI; cubre flujo conductor y admin | Ninguna | Regresión silenciosa en el flujo de cancelación | 9.5.3 |

---

### ALTAS — Bloquean funcionalidades importantes o Fase 10

| # | Módulo | Funcionalidad | Estado actual | Brecha detectada | Prioridad | Archivos involucrados | Acción recomendada | Criterio de aceptación | Dependencias | Riesgo si no se corrige | Fase |
|---|--------|---------------|---------------|-----------------|-----------|----------------------|--------------------|------------------------|--------------|------------------------|------|
| 7 | Backend | Tests Inmovilizaciones standalone | Parcial | Solo cubierto en contexto de InfraccionService; sin test propio de Inmovilizacion | 🟠 | `tests/Feature/InmovilizacionTest.php` (crear) | Crear tests para: inmovilizar, liberar, verificar estados, restricciones | Tests pasan; cubre inmovilizar vehículo y liberar con pago | #4 | Regresiones en inmovilización no detectadas | 9.5.3 |
| 8 | Backend | Deuda HasMiddleware | Incompatible Laravel 11 | `UsuarioController` y `RolController` usan `authorizeResource` en constructor | 🟠 | `app/Http/Controllers/UsuarioController.php`, `app/Http/Controllers/RolController.php` | Migrar a patrón `HasMiddleware` como el resto del proyecto | Controllers pasan sus tests existentes | Ninguna | Error 500 al gestionar usuarios/roles en producción | 9.5.3 |
| 9 | Backend | Vista Sesiones Parqueo backoffice | No existe | Solo disponible por API; sin UI de supervisión | 🟠 | `routes/web.php`, `app/Http/Controllers/SesionParqueoController.php` web, `resources/views/sesiones-parqueo/index.blade.php` | Crear vista solo-lectura con filtros (agente, zona, fecha, estado) | Comisario puede ver sesiones activas e historiales | Ninguna | Sin supervisión de ocupación en tiempo real desde el backoffice | 9.5.3 |
| 10 | Backend | Fiscalización — TurnoAgente | ✅ Resuelto (9.5.4) | TurnoAgente + FiscalizacionService + 3 API controllers + web controller + card móvil | ~~🟠~~ | — | — | ✅ Agente inicia/cierra turno desde app; backoffice ve turnos con duración | — | — | 9.5.4 |
| 11 | Backend | Fiscalización — RecorridoAgente | ✅ Resuelto (9.5.4) | RecorridoAgente + POST /api/v1/recorridos + GPS periódico 60s en dashboard móvil | ~~🟠~~ | — | — | ✅ App envía GPS cada 60s; recorrido visible en mapa Leaflet del backoffice | — | — | 9.5.4 |
| 12 | Backend | Fiscalización — IncidenteCalle | ✅ Resuelto (9.5.4) | IncidenteCalle + POST /api/v1/incidentes + pantalla `reportar-incidente.js` | ~~🟠~~ | — | — | ✅ Agente reporta incidente con tipo+descripción+GPS | — | — | 9.5.4 |
| 13 | Backend | OrdenPago | No existe | Obligación Art. 28 — proceso formal antes de cobrar multa | 🟠 | Crear: `OrdenPago` model + `OrdenPagoService` + controller API + vista backoffice | `php artisan make:model OrdenPago -mfs --api --requests --policy` | Comisario genera orden de pago por infracción; conductor recibe notificación | `Infraccion` (existente) | Riesgo legal: cobro de multa sin orden formal puede ser impugnable | 9.5.5 |
| 14 | Backend | Comprobante | No existe | Obligación Art. 19 — nota de venta por cada pago | 🟠 | Crear: `Comprobante` model + `ComprobanteService` + endpoint `GET /api/v1/comprobantes/{id}` | `php artisan make:model Comprobante -mfs --api --requests` | Sistema genera comprobante automáticamente al acreditar pago; conductor puede descargarlo | `TransaccionPago` (existente) | Incumplimiento tributario ante SRI; riesgo en Fase 10 (Tesorería) | 9.5.5 |
| 15 | Backend | ECU 911 stub | ✅ Resuelto (9.5.4) | `app/Services/integraciones/EcuNovecentonceService.php` — Log::info, retorna true. Llamado desde FiscalizacionService::registrarIncidente() | ~~🟠~~ | — | — | ✅ incidente.reportado_ecu911=true; interfaz lista para Fase 10 | — | — | 9.5.4 |

---

### MEDIAS — Mejoran calidad o cubren casos de uso importantes

| # | Módulo | Funcionalidad | Estado actual | Brecha detectada | Prioridad | Archivos involucrados | Acción recomendada | Criterio de aceptación | Dependencias | Riesgo si no se corrige | Fase |
|---|--------|---------------|---------------|-----------------|-----------|----------------------|--------------------|------------------------|--------------|------------------------|------|
| 16 | App Móvil | Cancelar ticket en estado `activo` | Parcial | App solo permite cancelar si `estado === 'pendiente_pago'`; backend permite también estado `activo` | 🟡 | `app/(conductor)/detalle-ticket.js` | Ampliar condición: mostrar botón si `estado in ['pendiente_pago', 'activo']` | Conductor puede cancelar ticket activo; backend valida reglas de negocio | Confirmar con backend cuáles estados son válidos para cancelar | Usuario no puede cancelar ticket activo que compró por error | 9.5.2 |
| 17 | Backend | Impugnacion | No existe | Conductor no puede impugnar una infracción por vía digital | 🟡 | Crear: `Impugnacion` model + `ImpugnacionService` + `POST /api/v1/infracciones/{id}/impugnacion` + pantalla en app | `php artisan make:model Impugnacion -mfs --api --requests --policy` | Conductor puede presentar impugnación; comisario la resuelve en backoffice | `Infraccion` (existente) | Proceso administrativo sin trazabilidad digital | 9.5.6 |
| 18 | Backend | NotificacionInfraccion | No existe | No hay boleta digital cuando se registra una infracción | 🟡 | Crear: `NotificacionInfraccion` model + trigger en `InfraccionService::registrar()` | Crear modelo + generar boleta al registrar infracción; enviar FCM si hay token | Conductor recibe push + puede ver boleta en app | `Infraccion` (existente), FCM (existente) | Conductor no es notificado digitalmente de la infracción | 9.5.6 |
| 19 | Backend | LiquidacionAgente | No existe | Art. 21: 60% de lo recaudado debe ir al agente mensualmente | 🟡 | Crear: `LiquidacionAgente` model + `LiquidacionService` + vista backoffice | `php artisan make:model LiquidacionAgente -mfs --requests` | Sistema calcula liquidación mensual por agente; director puede exportar | `TransaccionPago` (existente) | Incumplimiento del Art. 21 con agentes de parqueo | 9.5.5 |
| 20 | Backend | Vista Transacciones/Conciliación | No existe | Comisario/tesorero no puede ver transacciones de pago en backoffice | 🟡 | `routes/web.php`, controlador web, `resources/views/transacciones/index.blade.php` | Crear vista solo-lectura de transacciones con filtros y estado | Rol con permiso puede ver listado de transacciones + estado + monto | Ninguna | Sin auditoría de pagos desde el backoffice | 9.5.3 |

---

### BAJAS — Deuda técnica menor

| # | Módulo | Funcionalidad | Estado actual | Brecha detectada | Prioridad | Archivos involucrados | Acción recomendada | Criterio de aceptación | Dependencias | Riesgo si no se corrige | Fase |
|---|--------|---------------|---------------|-----------------|-----------|----------------------|--------------------|------------------------|--------------|------------------------|------|
| 21 | Backend | `AgenteParqueoService::autorizar` | Patrón viejo | No usa resolución cédula→correo→crear | 🟢 | `app/Services/AgenteParqueoService.php` | Aplicar mismo patrón que `PuntoVentaService::activar` | Tests de autorización de agente pasan sin crear cuentas duplicadas | Ninguna | Posible duplicado de cuentas si cédula ya existe | 9.5.7 |
| 22 | Backend | `VehiculoExonerado` sin suspensión | Incompleto | Solo tiene boolean `activo`; no hay acción suspender/reactivar sin eliminar | 🟢 | `app/Models/VehiculoExonerado.php`, controller, vista | Agregar acción `activar/desactivar` en controller y vista | Comisario puede suspender exoneración sin eliminarla | Ninguna | Comisario debe eliminar la exoneración para suspenderla | 9.5.3 |
| 23 | Backend | `AgenteParqueoFactory` vacía | Deuda técnica | Factory sin campos por defecto; tests crean agentes manualmente | 🟢 | `database/factories/AgenteParqueoFactory.php` | Completar factory con campos básicos y estados | Factories de tests existentes no fallan | Ninguna | Tests frágiles al escalar la suite | 9.5.7 |
| 24 | Backend | Push al acreditar Ticket/Infraccion | No activado | `Ticket::acreditar()` e `Infraccion::acreditar()` no disparan FCM | 🟢 | `app/Models/Ticket.php`, `app/Models/Infraccion.php`, `NotificacionPushService` | Activar `NotificacionPushService::encolar()` en ambos métodos `acreditar()` | Conductor recibe push cuando su pago es confirmado | FCM (existente) | Conductor no recibe confirmación de pago por push | 9.5.6 |

---

## Criterios de cierre de la Fase 9.5

La Fase 9.5 puede cerrarse cuando:

1. Los ítems 🔴 1–6 estén resueltos
2. Los ítems 🟠 7–9 estén resueltos
3. Al menos un ciclo de testing interno haya pasado con usuarios de prueba reales
4. La app móvil con usuario multi-rol haya sido probada manualmente (conductor+agente)
5. El flujo completo ticket→pago→comprobante esté implementado (ítems 13+14)

Los ítems 🟠 10–15 (Fiscalización) pueden completarse en paralelo con el testing interno si los recursos lo permiten.
