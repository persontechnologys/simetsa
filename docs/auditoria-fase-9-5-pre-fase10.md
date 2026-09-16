# Auditoría Fase 9.5 — Estado real del sistema pre-Fase 10

> Fecha de auditoría: 2026-06-06  
> Proyectos auditados: backend `/workspace/simetsa` (Laravel 11) · móvil `/workspace/simetsa-movil` (Expo SDK 56)  
> Rama activa: `fase-9-app-movil`

---

## 1. Resumen ejecutivo

1. **El backend tiene código completo para Fases 1–9**: 40 modelos, 52 controladores, 20 servicios, 544+ tests — la arquitectura es sólida y los módulos core funcionan.
2. **Hay 7 módulos solo documentados, sin código**: Fiscalización (Turnos/Recorridos/Incidentes), OrdenPago, Comprobante, Impugnación, NotificacionInfraccion, LiquidacionAgente/PuntoVenta.
3. **Bug crítico en app móvil**: usuario con dos roles (conductor + agente) siempre es redirigido al layout de conductor por un `if-else` secuencial en `app/index.js:21`. No existe selector de modo activo.
4. **Vistas backoffice faltantes**: Sesiones de parqueo, Cancelaciones, Transacciones/Pagos e Inmovilizaciones no tienen UI de gestión/supervisión en el backoffice.
5. **La app NO está lista para testing interno real** hasta resolver al menos el bug multirol (Fase 9.5.2) y añadir las vistas críticas de supervisión (Fase 9.5.3).

---

## 2. Estado real del sistema — Matriz por módulo

| Módulo | Modelos/BD | Servicios | Rutas Web | Rutas API | Vistas Blade | Tests | Seeders | Estado |
|--------|:---------:|:---------:|:---------:|:---------:|:------------:|:-----:|:-------:|--------|
| Usuarios y Roles | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| Catálogos base | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| Agentes de Parqueo | ✅ | ✅ | ✅ | — | ✅ | ✅ | ✅ | ✅ Completo |
| Puntos de Venta | ✅ | ✅ | ✅ | — | ✅ | ⚠️ | ✅ | ⚠️ Sin tests API |
| Conductores | ✅ | ✅ | ✅ | ✅ | ⚠️ solo lectura | ✅ | ✅ | ✅ Completo |
| Vehículos | ✅ | ✅ | ✅ | ✅ | ⚠️ solo lectura | ✅ | ✅ | ✅ Completo |
| Tickets | ✅ | ✅ | ✅ | ✅ | ⚠️ solo lectura | ✅ | ✅ | ⚠️ Sin creación web |
| Sesiones Parqueo | ✅ | ✅ | ❌ | ✅ | ❌ sin vista | ✅ | ✅ | ⚠️ Sin UI backoffice |
| Cancelaciones | ✅ | ✅ | ❌ | ❌ | ❌ sin vista | ❌ | ✅ | 🔴 Sin ruta/vista/tests |
| Pagos/Transacciones | ✅ | ✅ | ❌ | ✅ webhook | ❌ sin vista | ✅ | ✅ | ⚠️ Sin UI gestión |
| Infracciones | ✅ | ✅ | ✅ | ✅ | ⚠️ solo lectura | ✅ | ✅ | ✅ Completo |
| Inmovilizaciones | ✅ | ✅ | ❌ standalone | ✅ | ❌ embebida | ❌ standalone | ✅ | ⚠️ Sin vista/tests propios |
| Dashboard/Reportes | — | ✅ | ✅ | ✅ | ✅ | ✅ | — | ✅ Completo |
| Auth Móvil | ✅ | ✅ | — | ✅ | — | ✅ | ✅ | ✅ Completo |
| Fiscalización | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | 🔴 No existe |
| OrdenPago | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | 🔴 No existe |
| Comprobante | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | 🔴 No existe |
| Impugnacion | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | 🔴 No existe |
| NotificacionInfraccion | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | 🔴 No existe |
| LiquidacionAgente/PV | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | 🔴 No existe |

---

## 3. Módulos completamente implementados (con evidencia)

| Módulo | Evidencia de código |
|--------|-------------------|
| Usuarios & Roles | `app/Http/Controllers/UsuarioController.php`, `RolController.php`, `PerfilUsuario.php`, `RolPermisoSeeder.php` |
| Catálogos base | 9 modelos + vistas CRUD completas en `resources/views/zonas/`, `tarifas/`, etc. |
| Agentes Parqueo | `AgenteParqueoService`, `SolicitudAgenteService`, `CapacitacionService`, `AmonestacionService`, vistas Blade |
| Puntos de Venta | `PuntoVentaService`, `SolicitudPuntoVentaService`, vistas Blade |
| Conductores & Vehículos | `ConductorService`, `VehiculoService`, `CredencialDiscapacidadService`, API REST completa |
| Tickets | `TicketService` (Arts. 12–14, 22, 26, 27), `TicketController` API + web, `EstadoTicket` BackedEnum |
| Sesiones Parqueo | `SesionParqueoService`, `SesionParqueoController` API, `sesiones-parqueo` endpoint funcional |
| Pagos | `PagoManager`, `DeunaPaymentProvider` (stub), `TransaccionPago` polimórfico, webhook público |
| Infracciones | `InfraccionService` (Arts. 28–30), `InfraccionController` API + web, `InmovilizacionService` |
| Reportes & Dashboard | `ReporteService`, 4 reportes Excel/PDF, KPIs con polling AJAX |
| Auth Móvil | `MovilAuthController`, `UsuarioMovilResource`, `AuthContext` Expo con `hasRole`/`can` |
| App Conductor | 5 tabs + compra/historial/cancelación/vehículos/infracciones (Expo Router) |
| App Agente | 5 tabs + validar placa/iniciar sesión/registrar infracción/mapa (Expo Router) |

---

## 4. Módulos incompletos — brechas con archivos exactos

### 4.1 Cancelaciones — 🔴 Sin ruta web, sin endpoint cancelar-admin, sin tests

**Código existente:**
- Migración: `database/migrations/2026_05_29_100101_create_cancelacions_table.php`
- Modelo: `app/Models/Cancelacion.php` — relación 1:1 con Ticket, enum `TipoCancelacion`
- Servicio: lógica en `TicketService::cancelar()` y `TicketService::anular()`
- API conductor: `POST /api/v1/tickets/{ticket}/cancelar` funcional
- Seeder: `CancelacionSeeder.php`

**Faltante:**
- Ruta web: no existe `GET /cancelaciones` ni similar en `routes/web.php`
- Vista backoffice: no existe `resources/views/cancelaciones/` — el comisario/admin no puede ver el historial de cancelaciones en la web
- Tests: 0 tests específicos para `Cancelacion` — ni modelo ni controller ni servicio
- La app móvil solo permite cancelar si `estado === 'pendiente_pago'`; estados válidos para cancelar en backend no están claramente documentados

**Riesgo:** El comisario/admin no puede auditar cancelaciones desde el backoffice. Sin tests hay riesgo de regresión silenciosa.

---

### 4.2 Sesiones de Parqueo — ⚠️ Sin UI de supervisión backoffice

**Código existente:**
- Migración + modelo: `SesionParqueo` completo con relaciones
- API agente: `POST /api/v1/sesiones-parqueo` (iniciar), `GET /api/v1/sesiones-parqueo/{id}`
- Servicio: `SesionParqueoService::iniciar()`, `finalizar()`, `calcularTiempoExcedido()`
- Tests de API: `SesionParqueoApiTest.php`

**Faltante:**
- Ruta web: no existe `GET /sesiones-parqueo` en `routes/web.php`
- Vista backoffice: no existe `resources/views/sesiones-parqueo/` — supervisores no pueden ver sesiones activas ni historiales
- Fin de sesión automático: no hay job/comando que expire sesiones automáticamente (depende de `simetsa:sincronizar-estados-tickets`)

**Riesgo:** Sin vista de sesiones activas, el comisario no puede supervisar ocupación en tiempo real desde el backoffice.

---

### 4.3 Transacciones de Pago — ⚠️ Sin UI de gestión ni conciliación

**Código existente:**
- Modelo: `TransaccionPago` polimórfico (Ticket | Infraccion)
- Webhook: `PagoWebhookController` funcional
- `PagoManager`, `DeunaPaymentProvider` (stub)
- Seeder: `TransaccionPagoSeeder.php`
- API Resource: `TransaccionPagoResource`

**Faltante:**
- Ruta web: no existe `GET /transacciones` ni `GET /pagos` en backoffice
- Vista de conciliación: el comisario/tesorero no puede ver transacciones de pago desde la web
- El Reporte de Recaudación (Fase 8) agrega datos pero no permite gestión transacción a transacción
- No hay vista para ver estado de transacciones fallidas/pendientes

**Riesgo:** Sin UI de conciliación no hay forma de auditar pagos fallidos ni confirmar manualmente transacciones desde el backoffice.

---

### 4.4 Inmovilizaciones — ⚠️ Sin vista standalone ni tests propios

**Código existente:**
- Migración + modelo: `Inmovilizacion` completo (1:1 con Infraccion)
- API agente: `POST /api/v1/infracciones/{id}/inmovilizar`, `POST /{id}/liberar`
- Service: `InfraccionService::inmovilizar()`, `liberar()`
- Vista embebida: aparece dentro de `infracciones/show.blade.php`

**Faltante:**
- Vista standalone: no existe `resources/views/inmovilizaciones/` — el comisario no puede ver el listado de vehículos inmovilizados actualmente
- Tests propios: los tests de inmovilización están mezclados con los de Infracción; no hay `InmovilizacionTest.php` standalone
- No hay reporte/listado de "inmovilizaciones activas ahora"

**Riesgo:** Operativamente crítico — si no se puede ver qué vehículos están inmovilizados, no se puede gestionar su liberación desde el backoffice.

---

## 5. Funcionalidades faltantes (sin código en absoluto)

### 5.1 Módulo Fiscalización — No existe

**Documentado en:** `docs/inventario-modelos.md`

| Entidad | Propósito | Artículo Ordenanza |
|---------|-----------|--------------------|
| `TurnoAgente` | Inicio/fin de jornada del agente en calle | Art. 38 (obligaciones agente) |
| `RecorridoAgente` | Geolocalización GPS de la ruta del agente | Art. 38 (supervisión) |
| `IncidenteCalle` | Reporte de novedad desde app agente | Art. 38.m (ECU 911) |

**Verificación de ausencia:**
```
grep -r "Turno\|Recorrido\|Incidente\|Fiscalizacion" /app --include="*.php" -l
# → 0 resultados
```

---

### 5.2 Órdenes de Pago — No existe

**Documentado en:** `docs/inventario-modelos.md`

| Entidad | Propósito | Artículo Ordenanza |
|---------|-----------|--------------------|
| `OrdenPago` | Orden formal de pago por multa antes de generar cobro | Art. 28 (proceso de multas) |

---

### 5.3 Comprobantes — No existe

**Documentado en:** `docs/inventario-modelos.md` y `CLAUDE.md` (Decisiones cerradas)

| Entidad | Propósito | Artículo Ordenanza |
|---------|-----------|--------------------|
| `Comprobante` | Nota de venta interna, preparado para SRI | Art. 19 (documentación) |

---

### 5.4 Impugnaciones — No existe

**Documentado en:** `docs/inventario-modelos.md`

| Entidad | Propósito |
|---------|-----------|
| `Impugnacion` | Recurso del conductor contra una infracción (proceso administrativo) |

---

### 5.5 Notificación Infracción (Boleta Digital) — No existe

| Entidad | Propósito |
|---------|-----------|
| `NotificacionInfraccion` | Boleta digital que se genera cuando se registra una infracción al conductor |

*Nota: `NotificacionPush` genérica existe (Fase 5.G) pero no específica para infracciones.*

---

### 5.6 Liquidaciones — No existe

| Entidad | Propósito | Artículo Ordenanza |
|---------|-----------|--------------------|
| `LiquidacionAgente` | Distribución 60% al agente de lo recaudado | Art. 21 |
| `LiquidacionPuntoVenta` | Distribución 90% al punto de venta | Art. 21 |

---

## 6. Bugs detectados

### BUG CRÍTICO — App móvil: usuario con múltiples roles

**Severidad:** Alta — bloquea escenario de usuario conductor+agente

**Archivo:** `/workspace/simetsa-movil/app/index.js` líneas 21-24

```javascript
// Comportamiento actual (INCORRECTO para usuarios con 2 roles):
} else if (hasRole(ROLES.CONDUCTOR)) {
  router.replace('/(conductor)');
} else if (hasRole(ROLES.AGENTE)) {
  router.replace('/(agente)');
```

**Comportamiento actual:** Un usuario con roles `['conductor', 'agente_parqueo']` SIEMPRE es redirigido a `/(conductor)`. Nunca puede acceder al modo agente.

**Comportamiento esperado:** Si el usuario tiene ambos roles, debe aparecer una pantalla de selección de modo antes de ser redirigido al layout correspondiente.

**Archivos afectados:**
- `app/index.js:21-24` — redirección inicial
- `src/context/AuthContext.js:141-150` — sin estado `activeRole`
- `app/(conductor)/(tabs)/perfil.js` — sin botón "Cambiar a modo agente"
- `app/(agente)/(tabs)/perfil.js` — sin botón "Cambiar a modo conductor"
- `src/constants/config.js` — `ROLES_MOVIL` definido pero sin lógica de selección

**Campo legacy comprometido:**
```javascript
// src/context/AuthContext.js — backward compat problemático:
rol: usuario?.roles?.[0] ?? null  // SIEMPRE retorna el primer rol del array
```

**Criterio de aceptación para cierre:**
- Usuario con 1 rol: redireccionado directamente al layout de ese rol
- Usuario con 2+ roles: pantalla de selección antes de ingresar
- Usuario puede cambiar de modo desde su perfil sin hacer logout
- El layout de conductor no es accesible cuando el modo activo es agente y viceversa

---

### BUG MENOR — Cancelar ticket: estado `activo` no habilitado

**Archivo:** `/workspace/simetsa-movil/app/(conductor)/detalle-ticket.js`

```javascript
// Solo muestra botón cancelar si:
if (ticket.estado === 'pendiente_pago') { ... }
```

**Pregunta pendiente:** ¿Debería un conductor poder cancelar un ticket en estado `activo`? El `TicketService::cancelar()` en backend sí permite cancelar tickets activos (con reglas de negocio). La app no expone esa opción.

---

### DEUDA TÉCNICA INCOMPATIBLE — HasMiddleware en controllers de Fase 1

**Archivos:** `app/Http/Controllers/UsuarioController.php`, `app/Http/Controllers/RolController.php`

Usan `authorizeResource()` en constructor — incompatible con Laravel 11. Debe migrarse a `HasMiddleware` como el resto de controllers del proyecto.

---

## 7. Riesgos técnicos

| Riesgo | Descripción | Archivos | Impacto |
|--------|-------------|----------|---------|
| `AgenteParqueoService::autorizar` | Patrón viejo de creación de cuenta; no resuelve cédula→correo→crear | `app/Services/AgenteParqueoService.php` | Usuarios duplicados si cédula ya tiene cuenta |
| `UsuarioController`/`RolController` | `authorizeResource` en constructor incompatible Laravel 11 | 2 controllers | Error 500 en autorización |
| Reembolsos Deuna en `pendiente` | Sin procesador automático; quedaron en ese estado indefinidamente | `app/Models/Cancelacion.php` | Conductor espera reembolso que no llegará |
| Push al acreditar Ticket/Infraccion | `Ticket::acreditar()` e `Infraccion::acreditar()` no disparan FCM | 2 modelos | Conductor no recibe confirmación de pago |
| `AgenteParqueoFactory` vacía | Tests de Reportes crean agentes manualmente; sin factory estándar | `database/factories/AgenteParqueoFactory.php` | Tests frágiles al escalar |

---

## 8. Riesgos funcionales

| Riesgo | Descripción | Prioridad |
|--------|-------------|-----------|
| Usuario multi-rol en app | Conductor que es también agente pierde acceso a funciones de agente | CRÍTICO |
| Sin vista Inmovilizaciones activas | Comisario no puede ver qué vehículos están inmovilizados en tiempo real | ALTA |
| Sin vista Cancelaciones | No hay auditoría de cancelaciones en backoffice | ALTA |
| Sin gestión de Transacciones | No hay forma de investigar pagos fallidos/pendientes desde web | ALTA |
| Cancelar ticket solo `pendiente_pago` | Conductor no puede cancelar un ticket activo desde la app aunque el backend lo permita | MEDIA |
| Sin fin de sesión automático | Sesiones sin finalizar explícita en caso de falla de la app | MEDIA |

---

## 9. Riesgos legales y operativos

| Artículo | Obligación | Estado | Riesgo |
|----------|-----------|--------|--------|
| Art. 19 | Comprobante/nota de venta por cada pago | ❌ No implementado | Incumplimiento tributario ante SRI |
| Art. 21 | Liquidación mensual agentes (60/40) y puntos de venta (90/10) | ❌ No implementado | Incumplimiento contractual con agentes |
| Art. 28 | Orden de pago formal antes de cobrar multa | ❌ No implementado | Riesgo de impugnación legal por proceso irregular |
| Art. 38.m | Reportar incidentes a ECU 911 | ❌ No implementado | Incumplimiento obligación operativa |
| Art. 38 | Fiscalización y supervisión de turnos de agentes | ❌ No implementado | Sin auditoría de asistencia y recorridos |

---

## 10. Prioridades antes de testing interno

Para que la aplicación sea usable en un testing interno real, se necesita:

1. **[BLOQUEANTE]** Fix bug multi-rol en app móvil — sin esto, un usuario que es conductor+agente está atrapado en modo conductor
2. **[CRÍTICO]** Vista backoffice de inmovilizaciones activas — el comisario no puede operar sin ver qué vehículos están bloqueados
3. **[CRÍTICO]** Vista backoffice de cancelaciones — auditoría básica de operaciones
4. **[ALTA]** Tests de Cancelaciones e Inmovilizaciones standalone — detectar regresiones antes de testing real
5. **[ALTA]** Migrar `UsuarioController`/`RolController` a `HasMiddleware` — evitar errores en producción

---

## 11. Prioridades antes de Fase 10 (Integraciones Externas)

Para poder integrar CONADIS, ANT, ECU 911 y Tesorería, el sistema necesita tener cerrado:

1. **[BLOQUEANTE Fase 10]** Órdenes de Pago y Comprobantes — Tesorería Municipal necesita comprobantes
2. **[BLOQUEANTE Fase 10]** ECU 911 reporting stub — el módulo de Fiscalización necesita esta integración
3. **[ALTA]** Fiscalización (Turnos/Recorridos) — ECU 911 se activa desde incidentes de calle
4. **[ALTA]** Impugnaciones — proceso administrativo previo a ANT para retención de placa
5. **[MEDIA]** Liquidaciones — Tesorería Municipal opera sobre datos de liquidación

---

## 12. Evidencia en código

### Controladores API existentes (confirmados):
```
app/Http/Controllers/Api/TicketController.php         ✅
app/Http/Controllers/Api/SesionParqueoController.php  ✅
app/Http/Controllers/Api/InfraccionController.php     ✅
app/Http/Controllers/Api/PagoWebhookController.php    ✅
app/Http/Controllers/Api/MovilAuthController.php      ✅
```

### Módulos sin código (verificado por grep):
```bash
grep -r "TurnoAgente\|RecorridoAgente\|IncidenteCalle" app/ --include="*.php"
# → 0 resultados

grep -r "OrdenPago\|orden_pago" app/ --include="*.php"
# → 0 resultados (solo mención en Cobrable.php sin implementación)

grep -r "class Impugnacion\|ImpugnacionService" app/ --include="*.php"
# → 0 resultados

grep -r "class Comprobante\|ComprobanteService" app/ --include="*.php"
# → 0 resultados
```

### Rutas web faltantes (confirmado en routes/web.php):
```bash
grep -n "sesiones-parqueo\|cancelaciones\|transacciones" routes/web.php
# → 0 resultados
```

---

## 13. Diferencias roadmap vs realidad

| Fase documentada como ✓ | Lo que falta en código |
|------------------------|----------------------|
| Fase 5 ✓ (Tickets) | Vista backoffice de Cancelaciones; tests de Cancelación |
| Fase 5 ✓ (Sesiones) | Vista backoffice de Sesiones activas |
| Fase 6 ✓ (Pagos) | Vista backoffice de Transacciones; UI de conciliación |
| Fase 7 ✓ (Infracciones) | Vista standalone de Inmovilizaciones; tests propios de Inmovilización |
| Fase 9 ✓ (App Móvil) | Soporte de usuarios con múltiples roles (bug crítico) |

---

## 14. Recomendación final

### ¿Está lista la app para testing interno?

**No. La app NO está lista para testing interno real por las siguientes razones:**

1. **Bug bloqueante de múltiples roles**: cualquier usuario que sea conductor+agente (caso de uso operativo real) queda atrapado en modo conductor y pierde acceso a las funciones de agente.

2. **Sin UI de supervisión crítica en backoffice**: el comisario no puede ver inmovilizaciones activas, no puede auditar cancelaciones, y no puede investigar transacciones de pago fallidas.

3. **Sin tests de módulos críticos**: Cancelaciones e Inmovilizaciones no tienen tests propios, lo que hace imposible verificar regresiones antes de exponer el sistema a usuarios reales.

### ¿Qué debe cerrarse antes del testing interno?

- Fase 9.5.2 — Fix multi-rol app móvil (BLOQUEANTE)
- Fase 9.5.3 — Vistas backoffice faltantes + tests de cobertura

### ¿Qué puede dejarse para después del testing interno?

- Fase 9.5.4 — Fiscalización (Turnos/Recorridos/Incidentes) — operativamente importante pero no bloquea el testing del flujo core
- Fase 9.5.5 — Órdenes de Pago y Comprobantes — importante para cumplimiento legal pero no bloquea testing funcional
- Fase 9.5.6 — Impugnaciones — proceso administrativo que se puede probar después

### ¿Qué bloquea Fase 10?

- Módulo Órdenes de Pago y Comprobantes (Tesorería Municipal los necesita)
- Módulo Fiscalización básico con Incidentes (ECU 911 se conecta a esto)

### ¿Cuál debe ser el siguiente chat/fase?

**Fase 9.5.2 — Fix multi-rol app móvil**

```
Prompt recomendado:
"Estamos en Fase 9.5.2 del proyecto SIMETSA. La app móvil tiene un bug crítico donde un usuario 
con roles conductor+agente siempre es redirigido al layout de conductor por un if-else secuencial 
en app/index.js:21-24. 

Necesito implementar:
1. Agregar `activeRole` y `setActiveRole(rol)` al AuthContext en src/context/AuthContext.js
2. Nueva pantalla app/selector-rol.js que aparece cuando usuario.roles.length > 1
3. Modificar app/index.js: si roles.length > 1 → /selector-rol, si 1 rol → redirect directo
4. Actualizar app/(conductor)/(tabs)/perfil.js con botón 'Cambiar a modo agente'
5. Actualizar app/(agente)/(tabs)/perfil.js con botón 'Cambiar a modo conductor'
6. Guards en app/(conductor)/_layout.js y app/(agente)/_layout.js que verifiquen activeRole

Leer primero: docs/auditoria-app-movil-multirol.md y docs/plan-fases-post-9.md"
```
