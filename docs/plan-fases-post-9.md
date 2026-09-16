# Plan de Fases Post-9 — SIMETSA

> Generado: 2026-06-06  
> Contexto: Auditoría base en `docs/auditoria-fase-9-5-pre-fase10.md`  
> Brechas detalladas en `docs/backlog-cierre-modulos-criticos.md`

**Principio:** Cada sub-fase se trabaja en un **chat independiente** cargando el contexto desde los documentos de referencia. No pasar a la siguiente sub-fase hasta que la actual esté completa, probada y aprobada.

---

## Mapa de dependencias

```
Fase 9.5.1 (Auditoría)  ✅ ─→ Fase 9.5.2 (Multi-rol)    ✅ ─→ Testing interno
                             └─→ Fase 9.5.3 (Backoffice) ✅ ─→ Testing interno

Testing interno ─→ Fase 9.5.4 (Fiscalización)  ✅
               ─→ Fase 9.5.5 (Pagos/Comprobantes)   ✅ ─→ Bloqueo Fase 10 resuelto ✅
               ─→ Fase 9.5.6 (Impugnaciones/Notificaciones)   ⏳ SIGUIENTE
               ─→ Fase 9.5.7 (QA final) ─→ Fase 10
```

**Estado al 2026-06-07:** 9.5.1 ✅ · 9.5.2 ✅ · 9.5.3 ✅ · 9.5.4 ✅ · 9.5.5 ✅ · 9.5.6 ✅ · 9.5.7 ⏳

---

## Fase 9.5.1 — Auditoría y documentación ✅ Completada

**Objetivo:** Crear la matriz de brechas, plan técnico y auditoría de la app móvil multirol.

**Entregables creados:**
- `docs/auditoria-fase-9-5-pre-fase10.md`
- `docs/backlog-cierre-modulos-criticos.md`
- `docs/plan-fases-post-9.md` (este archivo)
- `docs/auditoria-app-movil-multirol.md`
- `docs/roadmap-fases.md` (actualizado con Fase 9.5)
- `CLAUDE.md` (actualizado con memoria operativa)

---

## Fase 9.5.2 — Fix multi-rol app móvil 🔴 BLOQUEANTE para testing

### Objetivo

Un usuario con roles `conductor` + `agente_parqueo` puede elegir su modo operativo, cambiar entre modos sin cerrar sesión, y acceder a todas sus funcionalidades.

### Alcance

Solo la app móvil (`/workspace/simetsa-movil`). El backend ya retorna `roles[]` correctamente.

### Archivos a revisar

- `docs/auditoria-app-movil-multirol.md` — diagnóstico completo y propuesta de solución
- `src/context/AuthContext.js` — estado del contexto actual
- `app/index.js` — lógica de redirección actual (el bug)
- `app/(conductor)/(tabs)/perfil.js` — pantalla de perfil conductor
- `app/(agente)/(tabs)/perfil.js` — pantalla de perfil agente
- `src/constants/config.js` — constantes de roles

### Archivos a modificar/crear

| Archivo | Cambio |
|---------|--------|
| `src/context/AuthContext.js` | Agregar `activeRole`, `setActiveRole`, persistencia SecureStore |
| `app/index.js` | Reemplazar if-else por lógica con `activeRole` |
| `app/selector-rol.js` | **Crear** — pantalla de selección de modo |
| `app/_layout.js` | Agregar `selector-rol` al stack raíz |
| `app/(conductor)/(tabs)/perfil.js` | Botón condicional "Cambiar a modo Agente" |
| `app/(agente)/(tabs)/perfil.js` | Botón condicional "Cambiar a modo Conductor" |
| `app/(conductor)/_layout.js` | Guard que verifica `activeRole === 'conductor'` |
| `app/(agente)/_layout.js` | Guard que verifica `activeRole === 'agente_parqueo'` |

### Modelos involucrados

Ninguno — cambio solo en frontend.

### Endpoints involucrados

- `GET /api/v1/movil/me` — verificar que retorna `roles[]` completo (ya está bien)

### Tareas técnicas

1. Leer `src/context/AuthContext.js` completo para entender el estado actual
2. Agregar `activeRole` (string|null) al estado del contexto
3. Agregar `setActiveRole(rol)` que valide que el usuario tenga ese rol y persista en SecureStore con key `simetsa_active_role`
4. Modificar `restaurarSesion()` para cargar `activeRole` desde SecureStore
5. Modificar `logout()` para borrar `simetsa_active_role` de SecureStore
6. Crear `app/selector-rol.js` con dos botones condicionales (Conductor / Agente)
7. Modificar `app/index.js`: si `roles.length > 1` y `!activeRole` → `/selector-rol`; si `activeRole` o 1 rol → redirect al layout
8. Actualizar `app/_layout.js` para incluir la ruta `selector-rol` en el Stack
9. Actualizar `app/(conductor)/(tabs)/perfil.js`: agregar sección condicional con botón de cambio de modo
10. Actualizar `app/(agente)/(tabs)/perfil.js`: igual para modo conductor
11. Verificar que `detalle-ticket.js` muestre botón cancelar también para estado `activo`

### Criterios de aceptación

Ver `docs/auditoria-app-movil-multirol.md` sección 10 — 13 criterios definidos.

### Tests necesarios

- Crear usuario de prueba con ambos roles en el backend para testing manual
- Verificar flujo manual completo: login → selector → modo conductor → cambiar → modo agente

### Riesgos

- El campo `rol` legacy (`roles[0]`) puede romper alguna pantalla que aún lo use — revisar todos los usos de `useAuth().rol`
- La pantalla `selector-rol` debe ser excluida del guard de autenticación (es pública post-login)

### Dependencias

Ninguna — puede trabajarse inmediatamente.

### Prompt recomendado para este chat

```
Estamos en Fase 9.5.2 del proyecto SIMETSA — Fix multi-rol en app móvil Expo.

La app está en /workspace/simetsa-movil (Expo SDK 56, JS puro, Expo Router).
El backend está en /workspace/simetsa (Laravel 11).

BUG: Un usuario con roles ['conductor', 'agente_parqueo'] siempre es redirigido al 
layout /(conductor) porque app/index.js:21-24 usa un if-else secuencial.

Contexto completo y propuesta de solución: leer docs/auditoria-app-movil-multirol.md
Plan de la fase: leer docs/plan-fases-post-9.md sección "Fase 9.5.2"

Implementar todos los cambios descritos en la propuesta de solución. Al finalizar:
- La app debe pasar los 13 criterios de aceptación de auditoria-app-movil-multirol.md
- No debe romperse ningún test existente del backend
```

---

## Fase 9.5.3 — Vistas backoffice faltantes y tests de cobertura

### Objetivo

El backoffice tiene visibilidad completa de: cancelaciones, sesiones activas, inmovilizaciones activas y transacciones de pago. Los módulos críticos tienen cobertura de tests.

### Alcance

Backend Laravel en `/workspace/simetsa`. Sin cambios en la app móvil.

### Archivos a revisar

- `routes/web.php` — rutas existentes para identificar patrón
- `app/Http/Controllers/InfraccionController.php` (web) — patrón a seguir
- `resources/views/infracciones/index.blade.php` — template de referencia
- `app/Models/Cancelacion.php` — relaciones y scopes existentes
- `app/Models/SesionParqueo.php` — relaciones y scopes existentes
- `app/Models/Inmovilizacion.php` — relaciones y scopes existentes
- `app/Models/TransaccionPago.php` — relaciones y scopes existentes
- `app/Http/Controllers/UsuarioController.php` — para migrar a HasMiddleware
- `app/Http/Controllers/RolController.php` — para migrar a HasMiddleware

### Archivos a crear

| Archivo | Descripción |
|---------|-------------|
| `app/Http/Controllers/CancelacionController.php` | Solo `index` + `show`, permiso `cancelaciones.ver` |
| `app/Http/Controllers/SesionParqueoWebController.php` | Solo `index`, permiso `sesiones_parqueo.ver` |
| `app/Http/Controllers/InmovilizacionWebController.php` | `index` + `show` + acción `liberar`, permiso correspondiente |
| `app/Http/Controllers/TransaccionPagoWebController.php` | Solo `index`, permiso `pagos.ver` |
| `resources/views/cancelaciones/index.blade.php` | Listado con filtros: fecha, tipo, ticket |
| `resources/views/sesiones-parqueo/index.blade.php` | Listado con filtros: agente, zona, estado, fecha |
| `resources/views/inmovilizaciones/index.blade.php` | Listado con badge de estado activo/liberado |
| `resources/views/transacciones/index.blade.php` | Listado con estado y monto |
| `tests/Feature/CancelacionTest.php` | Tests de flujo de cancelación |
| `tests/Feature/InmovilizacionTest.php` | Tests standalone de inmovilización |

### Archivos a modificar

| Archivo | Cambio |
|---------|--------|
| `routes/web.php` | Agregar 4 rutas nuevas: `cancelaciones`, `sesiones-parqueo` (web), `inmovilizaciones`, `transacciones` |
| `app/Http/Controllers/UsuarioController.php` | Migrar de `authorizeResource` a `HasMiddleware` |
| `app/Http/Controllers/RolController.php` | Migrar de `authorizeResource` a `HasMiddleware` |
| `config/simetsa_permisos.php` | Agregar permisos `cancelaciones.ver`, `transacciones.ver` si no existen |
| `database/seeders/RolPermisoSeeder.php` | Asignar nuevos permisos a roles comisario/director |

### Criterios de aceptación

- Comisario puede ver listado de cancelaciones con filtros
- Comisario puede ver listado de sesiones activas
- Comisario puede ver listado de inmovilizaciones activas con acción liberar
- Tests `CancelacionTest` y `InmovilizacionTest` pasan
- `UsuarioController` y `RolController` pasan sus tests existentes después de migrar

### Tests necesarios

- `CancelacionTest.php`: crear cancelación, verificar estado ticket, listar cancelaciones
- `InmovilizacionTest.php`: inmovilizar, liberar, verificar restricción de doble inmovilización

### Prompt recomendado

```
Estamos en Fase 9.5.3 del proyecto SIMETSA — Vistas backoffice faltantes y tests.

Backend en /workspace/simetsa (Laravel 11, PHP 8.2).
Contexto: docs/plan-fases-post-9.md sección "Fase 9.5.3"
Brechas detalladas: docs/backlog-cierre-modulos-criticos.md ítems 4, 5, 6, 7, 8, 9

Tareas:
1. Crear 4 controllers web de solo lectura: CancelacionController, SesionParqueoWebController, 
   InmovilizacionWebController, TransaccionPagoWebController
2. Crear vistas Blade correspondientes siguiendo el patrón de resources/views/infracciones/index.blade.php
3. Agregar rutas en routes/web.php con middleware de permisos correctos
4. Crear tests/Feature/CancelacionTest.php y InmovilizacionTest.php
5. Migrar UsuarioController y RolController de authorizeResource a HasMiddleware

Convenciones: ver app/Http/Controllers/CLAUDE.md y resources/views/CLAUDE.md
```

---

## Fase 9.5.4 — Módulo Fiscalización ✅ Completada (2026-06-06)

### Objetivo

Agentes de parqueo pueden registrar su turno, enviar posición GPS durante el recorrido, y reportar incidentes de calle. El backoffice puede supervisar turnos activos. El sistema tiene stub de ECU 911.

### Alcance

Backend Laravel + App Móvil (pantalla agente nueva o integración en Dashboard).

### Entregables creados

**Backend:**
- Migraciones: `turnos_agente`, `recorridos_agente`, `incidentes_calle` (orden correcto: 154019, 154021, 154022)
- Enums: `EstadoTurno` (iniciado/finalizado), `TipoIncidente` (accidente/obstruccion/conflicto/otro)
- Modelos: `TurnoAgente`, `RecorridoAgente`, `IncidenteCalle` — tabla explícita en español
- `FiscalizacionService` (5 métodos) + `EcuNovecentonceService` stub en `app/Services/integraciones/`
- API controllers en `Api/`: `TurnoAgenteController`, `RecorridoAgenteController`, `IncidenteCalleController`
- Web `TurnoAgenteController` (index + show, `$this->middleware()` en constructor)
- Rutas API (5 endpoints) + ruta web `Route::resource('turnos', ...)->only(['index','show'])`
- Vistas: `turnos/index.blade.php` + `turnos/show.blade.php` (mapa Leaflet con polilínea + incidentes)
- Breadcrumbs `turnos.index` y `turnos.show`
- `FiscalizacionTest.php`: 7 tests, 26 assertions — pasan todos
- `docs/api/fiscalizacion.md` con curls de referencia
- **7 tests nuevos → 577 total**

**App Móvil:**
- `app/(agente)/(tabs)/index.js` — card turno + botón Iniciar/Finalizar + GPS 60s (`setInterval`)
- `app/(agente)/reportar-incidente.js` — form: tipo, descripción, GPS automático
- `src/services/fiscalizacionService.js` — 4 funciones
- `src/constants/api.js` — 5 nuevos ENDPOINTS
- `app/(agente)/_layout.js` — registra `reportar-incidente` en el Stack

### Modelos a crear

| Modelo | Tabla | Campos clave | Relaciones |
|--------|-------|-------------|------------|
| `TurnoAgente` | `turnos_agente` | `agente_parqueo_id`, `inicio_at`, `fin_at`, `estado` (iniciado/finalizado), `observaciones` | BelongsTo `AgenteParqueo` |
| `RecorridoAgente` | `recorridos_agente` | `turno_agente_id`, `latitud`, `longitud`, `registrado_at` | BelongsTo `TurnoAgente` |
| `IncidenteCalle` | `incidentes_calle` | `turno_agente_id`, `tipo`, `descripcion`, `latitud`, `longitud`, `foto_evidencia`, `reportado_ecu911`, `notificado_at` | BelongsTo `TurnoAgente` |

### Comandos artisan

```bash
php artisan make:model TurnoAgente -mfs --api --requests --policy
php artisan make:resource TurnoAgenteResource
php artisan make:model RecorridoAgente -mfs --api --requests
php artisan make:resource RecorridoAgenteResource
php artisan make:model IncidenteCalle -mfs --api --requests --policy
php artisan make:resource IncidenteCalleResource
```

### Service a crear

`app/Services/FiscalizacionService.php` — métodos: `iniciarTurno`, `finalizarTurno`, `registrarPosicion`, `registrarIncidente`, `notificarEcuNovecientoonce`

### Endpoints API

| Método | URI | Descripción | Permiso |
|--------|-----|-------------|---------|
| POST | `/api/v1/turnos` | Iniciar turno | `turnos.iniciar` |
| PATCH | `/api/v1/turnos/{id}/finalizar` | Finalizar turno | `turnos.iniciar` |
| GET | `/api/v1/turnos/activo` | Ver turno activo del agente | `turnos.ver` |
| POST | `/api/v1/recorridos` | Enviar posición GPS | `turnos.iniciar` |
| POST | `/api/v1/incidentes` | Registrar incidente | `incidentes.registrar` |

### Vistas Blade (backoffice)

- `resources/views/turnos/index.blade.php` — listado de turnos con estado y duración
- `resources/views/turnos/show.blade.php` — detalle con recorrido en mapa Leaflet e incidentes

### Pantallas móviles (app agente)

Modificar `app/(agente)/(tabs)/index.js` (Dashboard) para agregar:
- Card de estado del turno actual (activo/inactivo)
- Botón "Iniciar Turno" / "Finalizar Turno"
- En turno activo: envío periódico de posición GPS (cada 60 seg)
- Botón "Reportar Incidente" que navega a nueva pantalla

Nueva pantalla: `app/(agente)/reportar-incidente.js`

### Criterios de aceptación

- Agente puede iniciar y finalizar turno desde la app
- Sistema almacena posición GPS del agente durante el turno activo
- Agente puede reportar incidente con tipo, descripción y GPS
- Sistema marca incidente como notificado a ECU 911 (stub)
- Backoffice muestra turnos activos con tiempo transcurrido

### Prompt recomendado

```
Estamos en Fase 9.5.4 del proyecto SIMETSA — Módulo de Fiscalización (Turnos/Recorridos/Incidentes).

Backend en /workspace/simetsa. App móvil en /workspace/simetsa-movil.
Contexto completo: docs/plan-fases-post-9.md sección "Fase 9.5.4"
Brechas: docs/backlog-cierre-modulos-criticos.md ítems 10, 11, 12, 15

Usar comandos artisan combinados según docs/comandos-artisan.md. 
Seguir convenciones de app/Services/CLAUDE.md para el FiscalizacionService.
El stub ECU 911 debe ser una clase en app/Services/integraciones/EcuNovecentonceService.php
para preparar Fase 10.
```

---

## Fase 9.5.5 — Órdenes de Pago y Comprobantes ✅ Completada (2026-06-06)

### Objetivo

Flujo completo: deuda pendiente → orden de pago formal → pago confirmado → comprobante generado. Cumplimiento de Art. 19 (comprobante) y Art. 28 (orden de pago).

### Entregables creados

**Backend:**
- 4 modelos: `OrdenPago` (tabla `ordenes_pago`), `Comprobante`, `LiquidacionAgente` (tabla `liquidaciones_agente`), `LiquidacionPuntoVenta` (tabla `liquidaciones_punto_venta`)
- 1 enum `EstadoOrdenPago` (pendiente/pagada/anulada/vencida) en `app/Enums/`
- `OrdenPagoService`: `generar` / `anular` / `verificarVencimientos` — guarda: solo 1 orden pendiente por infracción
- `ComprobanteService`: `generar` (idempotente: devuelve existente si ya hay uno) / `obtenerContenidoPdf` (Blade + layout 'impresion')
- `LiquidacionService`: `calcularPorAgente` (60%, join via sesiones_parqueo) / `calcularPorPuntoVenta` (90%, monto_bruto=0 hasta añadir punto_venta_id en tickets — deuda técnica)
- `PagoWebhookController` actualizado: inyecta `ComprobanteService`, llama `generar()` tras `acreditar()` en try/catch (webhook no falla si comprobante falla)
- `ComprobantePolicy` (conductor solo ve los suyos) + `OrdenPagoPolicy`
- 2 API controllers: `ComprobanteApiController` (GET show + GET pdf), `OrdenPagoApiController` (POST store)
- 3 web controllers: `OrdenPagoController` (index+show+anular), `ComprobanteController` (index), `LiquidacionController` (index — union agentes/PV)
- 5 vistas Blade: `ordenes-pago/{index,show}`, `comprobantes/{index,pdf}`, `liquidaciones/index`
- Rutas: 3 API (`/api/v1/comprobantes/{id}`, `/api/v1/comprobantes/{id}/pdf`, `/api/v1/ordenes-pago`) + 4 web
- Breadcrumbs: `ordenes-pago.index`, `ordenes-pago.show`, `comprobantes.index`, `liquidaciones.index`
- `ComprobanteTest.php`: 7 tests, 23 assertions — todos pasan
- **7 tests nuevos → 584 total**

**Decisiones:**
- PDF = HTML imprimible vía `layouts.impresion` (sin wkhtmltopdf/dompdf) — mismo patrón Fase 8
- `Comprobante`/`OrdenPago` sin SoftDeletes → `max('id')` en lugar de `withTrashed()->max('id')`
- FK reales: `agentes_parqueo` (no `agente_parqueos`), `puntos_venta` (no `punto_ventas`)
- `memory_limit = 512M` en `phpunit.xml` (límite por defecto del entorno era 128MB)

### Modelos creados

| Modelo | Tabla | Campos clave | Relaciones |
|--------|-------|-------------|------------|
| `OrdenPago` | `ordenes_pago` | `infraccion_id`, `numero_orden` (OP-0001), `monto`, `estado`, `vence_at`, `generada_por`, `motivo_anulacion` | BelongsTo `Infraccion`, `User` |
| `Comprobante` | `comprobantes` | `concepto_type`, `concepto_id` (morphTo), `numero` (CB-0001), `monto`, `fecha_emision`, `pdf_path` | MorphTo `concepto` (Ticket \| Infraccion) |
| `LiquidacionAgente` | `liquidaciones_agente` | `agente_parqueo_id`, `periodo_mes`, `monto_bruto`, `porcentaje` (60%), `monto_neto` | BelongsTo `AgenteParqueo` |
| `LiquidacionPuntoVenta` | `liquidaciones_punto_venta` | `punto_venta_id`, `periodo_mes`, `monto_bruto`, `porcentaje` (90%), `monto_neto` | BelongsTo `PuntoVenta` |

---

## Fase 9.5.6 — Impugnaciones y Notificaciones de Infracción ✅ Completada (2026-06-07)

### Entregables creados

**Backend:**
- 2 migraciones: `impugnaciones` + `notificaciones_infraccion` (con UNIQUE infraccion+conductor)
- 2 modelos: `Impugnacion` (estados: pendiente/admitida/rechazada/resuelta) + `NotificacionInfraccion` (boleta digital)
- `ImpugnacionService`: `presentar` / `admitir` / `rechazar` / `resolver`
- `NotificacionInfraccionService`: `notificar` (best-effort: crea boleta + push FCM silencioso si falla)
- `InfraccionService::registrar()` actualizado: trigger de notificación para conductor_id no null
- `ImpugnacionApiController` extiende `ApiController`: POST/GET `/api/v1/infracciones/{id}/impugnacion`
- `ImpugnacionController` web: index + show + admitir + rechazar + resolver (`$this->middleware()` en constructor)
- 2 vistas Blade: `impugnaciones/index.blade.php` (filtros: estado, agente, fecha) + `show.blade.php` (acciones por rol)
- 7 rutas registradas (2 API + 5 web) + breadcrumbs `impugnaciones.index` y `impugnaciones.show`
- `ImpugnacionResource` + `StoreImpugnacionRequest`
- Relaciones añadidas en `Infraccion`: `impugnacion()` (HasOne) + `notificaciones()` (HasMany)
- `ImpugnacionTest.php`: 8 tests, 17 assertions — todos pasan
- **8 tests nuevos → 592 total**

**App Móvil:**
- `src/constants/api.js`: nuevo endpoint `IMPUGNACION(id)`
- `src/services/infraccionesService.js`: `impugnarInfraccion` + `obtenerImpugnacion`
- `app/(conductor)/detalle-infraccion.js`: botón "Impugnar" + modal de motivo + badge de estado de impugnación existente

**Decisiones:**
- FCM best-effort: `NotificacionInfraccionService` captura cualquier error de push y continúa — el registro de infracción nunca falla por un push fallido
- Permisos (`impugnaciones.ver`, `impugnaciones.registrar`, `impugnaciones.resolver`) ya estaban declarados en `config/simetsa_permisos.php` y `RolPermisoSeeder` desde Fases anteriores
- `ImpugnacionApiController` extiende `ApiController` (no implementa `HasMiddleware`) — permisos aplicados en `routes/api.php`
- Web controller usa `$this->middleware()` en constructor (patrón del proyecto, no `HasMiddleware` estático)

### Objetivo

Un conductor puede impugnar una infracción digitalmente. El sistema notifica al conductor cuando le llega una infracción (boleta digital). El comisario puede resolver impugnaciones desde el backoffice.

### Modelos a crear

| Modelo | Tabla | Campos clave | Relaciones |
|--------|-------|-------------|------------|
| `Impugnacion` | `impugnaciones` | `infraccion_id`, `conductor_id`, `motivo`, `estado` (pendiente/admitida/rechazada/resuelta), `resolucion`, `resuelto_por`, `resuelto_at` | BelongsTo `Infraccion`, `Conductor` |
| `NotificacionInfraccion` | `notificaciones_infraccion` | `infraccion_id`, `conductor_id`, `leida_at`, `enviada_push` | BelongsTo `Infraccion`, `Conductor` |

### Trigger automático

Activar `NotificacionInfraccionService::notificar(Infraccion)` dentro de `InfraccionService::registrar()` cuando `conductor_id` no es null.

### Endpoints API

| Método | URI | Descripción | Permiso |
|--------|-----|-------------|---------|
| POST | `/api/v1/infracciones/{id}/impugnacion` | Conductor presenta impugnación | `infracciones.ver` |
| GET | `/api/v1/infracciones/{id}/impugnacion` | Ver estado de impugnación | `infracciones.ver` |

### Pantallas móviles

Modificar `app/(conductor)/detalle-infraccion.js`:
- Si infracción en estado `pendiente`, mostrar botón "Impugnar"
- Si ya existe impugnación, mostrar estado de la misma

### Criterios de aceptación

- Conductor puede impugnar infracción desde la app
- Comisario puede ver listado de impugnaciones y resolverlas en backoffice
- Conductor recibe push y notificación en bandeja cuando se registra una infracción a su vehículo

### Prompt recomendado

```
Estamos en Fase 9.5.6 del proyecto SIMETSA — Impugnaciones y Notificaciones de Infracción.

Backend en /workspace/simetsa. App móvil en /workspace/simetsa-movil.
Contexto: docs/plan-fases-post-9.md sección "Fase 9.5.6"
Brechas: docs/backlog-cierre-modulos-criticos.md ítems 17, 18, 24

El trigger de NotificacionInfraccion debe ir dentro de InfraccionService::registrar() 
(ya existe) — agregar la llamada al nuevo service solo cuando conductor_id no es null.
El push debe usar el FCMService existente (Fase 6.B) y el DispositivoMovil existente.
```

---

## Fase 9.5.7 — QA funcional y preparación Fase 10

### Objetivo

Verificar el sistema de extremo a extremo, cerrar deuda técnica menor, y preparar los stubs/interfaces que Fase 10 necesita para las integraciones externas.

### Tareas técnicas

1. **Testing end-to-end del flujo core:**
   - Conductor compra ticket → agente valida placa → sesión inicia → ticket expira → multa → pago → comprobante
   - Verificar con usuario multi-rol (conductor+agente)

2. **Auditoría de permisos:**
   - Revisar que todos los endpoints API tengan `permission:` middleware correcto
   - Revisar que las vistas no muestren acciones sin verificar permiso en backend
   - Revisar `config/simetsa_permisos.php` vs permisos asignados en `RolPermisoSeeder`

3. **Deuda técnica menor:**
   - `AgenteParqueoService::autorizar` — aplicar patrón cédula→correo→crear (ítem 21)
   - `AgenteParqueoFactory` — completar con campos por defecto (ítem 23)
   - `VehiculoExonerado` — agregar acción activar/desactivar (ítem 22)

4. **Preparación de interfaces para Fase 10:**
   - `app/Services/integraciones/ConadisService.php` (stub) — para validación CONADIS
   - `app/Services/integraciones/AntService.php` (stub) — para validación de placas ANT
   - `app/Services/integraciones/EcuNovecentonceService.php` (stub) — ya creado en Fase 9.5.4
   - `app/Services/integraciones/TesoreriaService.php` (stub) — para conciliación con Tesorería Municipal

5. **Documentación final:**
   - Actualizar `docs/roadmap-fases.md` con estado de cada sub-fase 9.5
   - Actualizar `CLAUDE.md` con Fase 9.5 cerrada y Fase 10 lista para iniciar

### Criterios de aceptación

- Flujo core funciona de extremo a extremo sin errores
- Suite completa de tests pasa (`php artisan test`)
- Los 4 stubs de integración existen con interfaz definida
- Sin deuda técnica de prioridad 🔴 o 🟠 pendiente

### Prompt recomendado

```
Estamos en Fase 9.5.7 del proyecto SIMETSA — QA funcional y preparación Fase 10.

Backend en /workspace/simetsa. App móvil en /workspace/simetsa-movil.
Contexto: docs/plan-fases-post-9.md sección "Fase 9.5.7"

Verificar que php artisan test pasa limpio. Crear los 4 stubs de integración en 
app/Services/integraciones/ con interfaz PHP (interface + stub implementation).
Completar deuda técnica de items 21, 22, 23 del backlog-cierre-modulos-criticos.md.
Al finalizar actualizar CLAUDE.md con Fase 9.5 cerrada y Fase 10 como siguiente paso.
```

---

## Fase 10 — Integraciones Externas

**Estado:** Bloqueada hasta completar Fase 9.5.5 (comprobantes/órdenes de pago) y Fase 9.5.4 (ECU 911 stub).

**Módulos:**
- CONADIS: validación de discapacidad en tiempo real (reemplaza `ConadisService` stub)
- ANT: validación de placas activas (reemplaza `AntService` stub)
- ECU 911: reporte de incidentes en tiempo real (reemplaza `EcuNovecentonceService` stub)
- Tesorería Municipal: conciliación de cobros (reemplaza `TesoreriaService` stub)

**Prompt recomendado para iniciar Fase 10:**

```
Estamos iniciando Fase 10 del proyecto SIMETSA — Integraciones Externas.

Backend en /workspace/simetsa. La Fase 9.5 está completamente cerrada.
Los 4 stubs de integración ya existen en app/Services/integraciones/.

Comenzar con la integración que tenga documentación API disponible. 
Si no hay credenciales reales, mantener el stub pero implementar el flujo completo
con autenticación, manejo de errores y reintentos.
```

---

## Fase 11 — Despliegue y Puesta en Producción

Pendiente de definición con el GAD Municipal de Salcedo: servidor, hardening, SSL, backups, capacitación al personal.
