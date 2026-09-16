# SIMETSA — Memoria del proyecto

Sistema Municipal de Estacionamiento Tarifado del Cantón Salcedo (`pry_simetsa`).
Base legal: **Ordenanza SIMETSA**, GAD Municipal de Salcedo, aprobada **06-feb-2020** y sancionada **10-feb-2020**.
**Citar el artículo correspondiente** en PHPDoc/comentarios cuando una regla provenga de la Ordenanza.
Texto canónico: `docs/legal/Ordenanza_SIMETSA.pdf`. Resumen operativo (artículo por artículo) para consulta rápida: `docs/legal/ordenanza-simetsa.md`.

Dos productos en dos repositorios:

1. **Plataforma Web (Backoffice)** — administración municipal, Comisaría, Dirección de Seguridad, agentes y puntos de venta. Ruta local: `/home/persontech/workspace/simetsa`
2. **Aplicación Móvil** — conductores y agentes en calle (consume la API REST). Ruta local: `/home/persontech/workspace/simetsa-movil`

> Al trabajar en cualquier tarea que involucre la app móvil, abrir TAMBIÉN el proyecto `/workspace/simetsa-movil`.

---

## Stack

- **Laravel 11 puro** (sin Livewire / Inertia / Filament), **PHP 8.2+**, **PostgreSQL**.
- TZ `America/Guayaquil`, locale `es`.
- Web: Blade + Bootstrap 5 + Leaflet.js (tiles OSM).
- Móvil: **Expo** (SDK, JS, no TS) + `expo-location` + `react-native-maps` (OSM) + API REST con Sanctum.
- Paquetes: Breeze (auth web), Sanctum (auth API), Spatie Permission (roles/permisos).
- Externos cerrados: **Deuna** (pasarela de pagos, stub en Fase 6), **Firebase Cloud Messaging** (push), `storage/app/public` (archivos).

> Si necesitás un paquete nuevo, **proponelo con justificación** antes de instalarlo.

---

## Reglas estrictas

- **NO** modificar la estructura por defecto de Laravel, ni el modelo `User`, ni la tabla `users`, ni las migraciones originales de Laravel/Breeze/Sanctum/Spatie.
- Para extender el usuario usar el modelo **`PerfilUsuario`** (1:1) con el trait **`TienePerfilUsuario`**.
- Todo en **español**:
  - Modelos: singular (`Vehiculo`, `Zona`, `Plaza`).
  - Tablas: plural (`vehiculos`, `zonas`, `plazas`).
  - Controllers, rutas, variables, métodos, comentarios, mensajes al usuario.
- Permisos formato `modulo.accion` (ej. `agentes.editar`).
- **PHPDoc obligatorio** en clases y métodos públicos. Comentarios en bloques de lógica de negocio. Encabezado en cada archivo. **Citar la Ordenanza** cuando aplique.

### Convenciones detalladas por capa

- Controllers web y API: ver `app/Http/Controllers/CLAUDE.md`.
- Servicios (lógica de negocio): ver `app/Services/CLAUDE.md`.
- Vistas Blade + Bootstrap: ver `resources/views/CLAUDE.md`.

---

## Comandos artisan — regla de eficiencia

**Nunca** crear archivos uno por uno. Usar siempre flags combinados:

```bash
# Backoffice con vistas (8 archivos en un comando):
php artisan make:model NombreEntidad -mcrfs --requests --policy

# API móvil sin vistas (9 archivos en 2 comandos):
php artisan make:model NombreEntidad -mfs --api --requests --policy
php artisan make:resource NombreEntidadResource
```

Services/Actions se crean a mano en `app/Services` / `app/Actions`.

Tabla completa de flags y comandos complementarios (observer, test, job, notification, event/listener) en `docs/comandos-artisan.md`.

---

## Cumplimiento legal y privacidad

Diseñar pensando en la **LOPDP** (Ley Orgánica de Protección de Datos Personales del Ecuador): consentimiento informado al registrar, logs de auditoría para operaciones sensibles, política de retención.

---

## Roles del sistema

Vía Spatie (enum `App\Enums\RolSistema`):
`super_admin`, `comisario`, `director_seguridad`, `agente_parqueo`, `punto_venta`, `conductor`.

Usuarios de prueba sembrados por `UsuarioPruebaSeeder` (password = `password`):

| Correo | Rol |
|---|---|
| `admin@simetsa.gob.ec` | super_admin |
| `comisario@simetsa.gob.ec` | comisario |
| `director.seguridad@simetsa.gob.ec` | director_seguridad |
| `agente@simetsa.gob.ec` | agente_parqueo (AG-0001) |
| `puntoventa@simetsa.gob.ec` | punto_venta (PV-0001) |
| `conductor@simetsa.gob.ec` | conductor |

Cédulas válidas para tests: `1710034065`, `1102345677`. En tests de activación (perfil único) usar cédulas sintéticas `0999…` para no chocar con las sembradas.

---

## Estado actual

- **Fase 0** ✓ Configuración inicial.
- **Fase 1** ✓ Roles, permisos, perfiles de usuario.
- **Fase 2** ✓ Catálogos base (zonas, calles, manzanas, plazas, tipos, tarifas, horarios, feriados, parámetros).
- **Fase 3** ✓ Agentes de Parqueo (3.A–3.D) y Puntos de Venta (3.E.1–3.E.2).
- **Fase 4** ✓ Conductores y Vehículos (API móvil + backoffice supervisión). 4.A TipoVehiculo CRUD backoffice + API read-only. 4.B Vehiculo API CRUD (Sanctum, ownership). 4.C CredencialDiscapacidad CONADIS (Art. 26). 4.D Backoffice conductores + VehiculoExonerado (Art. 27). 55 tests.
- **Fase 5** ✓ Sistema de Tickets Digitales. 5.A modelos/migraciones/enums. 5.B TicketService (Arts. 12–14, 22, 26, 27) + 21 tests de borde. 5.C API conductor (comprar, historial, cancelar). 5.D API agente (validar placa, iniciar sesión). 5.E–5.F Backoffice supervisión y anulación. 5.G FCM placeholder (dispositivos + cola lógica). 68 tests (358 total). Decisiones: EstadoTicket como BackedEnum PHP 8.2, SesionParqueo 1:1 con Ticket, Cancelacion unifica conductor/admin con tipo enum.
- **Fase 6** ✓ Pagos multi-proveedor + FCM real. 6.0 MetodoPago→dos campos (metodo_pago+proveedor), ProveedorPago enum, gate PagoSimulado por entorno, comando simetsa:sincronizar-estados-tickets. 6.A PaymentProviderInterface + Cobrable + TransaccionPago polimórfica + DeunaPaymentProvider (fake, sin HTTP) + PagoManager. 6.B kreait/laravel-firebase + EnviarNotificacionFCMJob (retry×3, lazy FCMService) + interruptor FCM_ENABLED. 6.C PendientePago en EstadoTicket, EstadoReembolso en Cancelacion, PagoWebhookController (POST /api/v1/pagos/webhook/{proveedor}, público). 393 tests (35 nuevos). Decisiones: proveedor explícito en request, PagoSimulado gateado, calcularEstadoActual() público, webhook registrado en modo fake.
- **Fase 7** ✓ Infracciones e Inmovilización. 7.A modelos/migraciones/enums (TipoInfraccion 12 casos, EstadoInfraccion, EstadoInmovilizacion) + Infraccion implements Cobrable + Inmovilizacion 1:1. 7.B InfraccionService (calcularMulta Arts. 28–30, registrar, inmovilizar, liberar, anular). 7.C API agente (POST /infracciones, GET /{id}, POST /{id}/inmovilizar, POST /{id}/liberar). 7.D API conductor (GET /conductor/infracciones, POST /{id}/pagar vía PagoManager; webhook genérico acredita y libera candado Art. 15). 7.E Backoffice web (index con 7 filtros, show + inmovilización + transacciones embebidas, anular modal). Cleanup: docs/api/infracciones.md completo (agente + conductor), InmovilizacionSeeder implementado, stub huérfano eliminado. 80 tests nuevos, 473 total. Decisiones: TipoInfraccion BackedEnum PHP 8.2 (12 casos cerrados por Ordenanza), Inmovilizacion entidad propia 1:1, monto_multa snapshot con sbu_vigente, NegarPago (Art. 17.g) sin cargo económico, conductor_id nullable.
- **Fase 8** ✓ Reportes y Dashboard. 8.A Dashboard KPIs (6 tarjetas + 3 gráficos Chart.js, polling AJAX 60 s, cache 5 min). 8.B Reporte de Recaudación (filtros: fecha/tipo/proveedor/zona, tabla paginada, Excel via Maatwebsite, PDF Blade imprimible, layout `impresion`). 8.C Reporte de Infracciones (filtros: fecha/estado/tipo/zona/agente, inmovilizaciones, Excel + PDF). 8.D Reporte de Ocupación (barras por día/hora, doughnut por zona, totales: sesiones, duración promedio, hora pico). Paquete nuevo: `maatwebsite/excel`. 56 tests nuevos, 529 total. Decisiones: Maatwebsite para Excel + Blade imprimible para PDF (sin binarios externos), Cache Laravel 5 min para KPIs, queries directas con `whereHasMorph` para filtro zona en recaudación, JSON_PRESERVE_ZERO_FRACTION en endpoint kpis, dashboard accesible a todos los roles (KPIs visibles solo con `kpi.ver`).
- **Fase 9** ✓ App Móvil Expo SDK 56 (`/workspace/simetsa-movil`, JS puro). 9.A ✓ Scaffolding. 9.B ✓ Auth global unificada (MovilAuthController + AuthContext con hasRole/can, 544 tests). 9.C ✓ App conductor (5 tabs + comprar ticket + historial + detalles). 9.D ✓ App agente (5 tabs + validar placa + infracciones + inicio sesión inline Art. 16). 9.E ✓ Mapa OSM con polígono de zona + GPS. 9.F ✓ FCM directo (development build). 9.G ✓ Pulido: 8 stubs eliminados (routing limpio), pago_simulado gateado con __DEV__, EXPO_PUBLIC_API_URL env var, eas.json. 21/21 expo-doctor. Docs: `docs/api/movil-auth.md`, `docs/mobile/auth-global.md`.
- **Fase 9.5** ✓ Auditoría funcional y cierre de módulos críticos. **FASE 9.5 COMPLETAMENTE CERRADA** (2026-06-08). **Fase 10 desbloqueada.** Ver `docs/plan-fases-post-9.md`. Auditoría base: `docs/auditoria-fase-9-5-pre-fase10.md`. Stubs de integración: `app/Services/integraciones/`.
  - **9.5.1** ✓ Auditoría y documentación (matriz de brechas, plan, auditoría multirol).
  - **9.5.2** ✓ Fix multi-rol app móvil. `activeRole`/`setActiveRole` en `AuthContext`, pantalla `selector-rol.js`, guards en layouts conductor/agente, botones "Cambiar modo" en perfiles. 8 archivos en `/workspace/simetsa-movil`.
  - **9.5.3** ✓ Vistas backoffice + tests. `CancelacionController`, `SesionParqueoWebController`, `InmovilizacionWebController`, `TransaccionPagoWebController`. `LiberarInmovilizacionRequest`. 6 vistas Blade (index+show cancelaciones e inmovilizaciones, index sesiones y transacciones). Breadcrumbs. `CancelacionTest` (12) + `InmovilizacionTest` (14). `UsuarioController`/`RolController` migrados de `authorizeResource` a `$this->middleware()` en constructor. 26 tests nuevos, 570 total.
  - **9.5.4** ✓ Módulo Fiscalización — 3 modelos (TurnoAgente, RecorridoAgente, IncidenteCalle), 2 enums (EstadoTurno, TipoIncidente), FiscalizacionService (5 métodos), EcuNovecentonceService stub (Art. 38.m), 3 API controllers (5 endpoints), web TurnoAgenteController (index+show con mapa Leaflet+recorrido), 7 tests pasan, 577 total. App móvil: card turno+GPS periódico 60s en dashboard agente, pantalla reportar-incidente.js. Docs: docs/api/fiscalizacion.md.
  - **9.5.5** ✓ Órdenes de Pago y Comprobantes (Arts. 19, 21, 28). 4 modelos (OrdenPago, Comprobante, LiquidacionAgente, LiquidacionPuntoVenta) + 1 enum (EstadoOrdenPago). OrdenPagoService (generar/anular/verificarVencimientos), ComprobanteService (generar idempotente + PDF Blade), LiquidacionService (agente 60% / PV 90%). Webhook actualizado: genera comprobante al acreditar (Art. 19). API: GET/PDF comprobantes + POST ordenes-pago. Backoffice: vistas ordenes-pago (index+show+anular), comprobantes (index), liquidaciones (index). 7 tests nuevos, 584 total. Decisiones: PDF = HTML imprimible layout 'impresion' (sin PDF libs), withTrashed() removido (modelos sin SoftDeletes), monto_bruto PV = 0 hasta añadir punto_venta_id en tickets.
  - **9.5.6** ✓ Impugnaciones y Notificaciones de Infracción (Art. 17.f). 2 modelos (Impugnacion, NotificacionInfraccion) + 2 tablas. ImpugnacionService (presentar/admitir/rechazar/resolver). NotificacionInfraccionService (best-effort: crea boleta + intenta push FCM, nunca interrumpe el flujo principal). InfraccionService::registrar() actualizado con trigger de notificación. 2 API endpoints (POST/GET /infracciones/{id}/impugnacion). Web ImpugnacionController (index+show+admitir+rechazar+resolver, `$this->middleware()` en constructor). 2 vistas Blade. App móvil: detalle-infraccion.js con botón "Impugnar" + modal de motivo + estado de impugnación existente. 8 tests nuevos, 592 total. Decisiones: FCM best-effort (falla silenciosa en NotificacionInfraccionService), ImpugnacionResource extiende ApiController, permisos ya estaban en config/simetsa_permisos.php y RolPermisoSeeder.
  - **9.5.7** ✓ QA funcional + preparación Fase 10. 7 grupos de tests en verde (592 tests). Deuda técnica resuelta: `AgenteParqueoFactory` completa con campos realistas + estados `suspendido`/`terminado`; `VehiculoExonerado` toggle activar/desactivar (Art. 27) — ruta PATCH, controller, vista, 2 tests; docs `AgenteParqueoService` actualizados. 3 stubs de integración creados en `app/Services/integraciones/`: `ConadisService` (Art. 26), `AntService`, `TesoreriaService` (Arts. 19, 21).

Roadmap completo (Fases 4–11 con detalle): ver `docs/roadmap-fases.md`.
Inventario de los ~55-60 modelos por módulo: ver `docs/inventario-modelos.md`.

---

## Deuda técnica abierta

- **Patrón de autorización en controllers**: el proyecto usa `$this->middleware()` en constructor (NO el patrón estático `HasMiddleware`) porque `Illuminate\Routing\Controller::middleware()` es no-estático y PHP no permite sobreescribirlo con estático. `UsuarioController` y `RolController` ya migrados (Fase 9.5.3).
- **Comando `simetsa:marcar-credenciales-vencidas`**: transicionar credenciales CONADIS con `fecha_vencimiento < today()` a estado `vencida`. Pendiente de mantenimiento.
- **`sesiones_parqueo.ver` no asignado a conductor**: el conductor ve la sesión embebida en `TicketResource` (relación `whenLoaded`); no tiene acceso directo al endpoint `/sesiones-parqueo/{id}`.
- **Reembolsos Deuna pendientes sin procesador**: `Cancelacion.estado_reembolso = pendiente` queda sin acción automática hasta integrar el endpoint de reembolso de Deuna (requiere credenciales reales; Fase 8 o posterior).
- **Notificación push al acreditar ticket**: `Ticket::acreditar()` no dispara notificación TIPO_EXPIRA_PRONTO todavía (pendiente activar `NotificacionPushService` en ese punto).
- **Notificación push al acreditar multa**: `Infraccion::acreditar()` no dispara push FCM al conductor cuando el pago se confirma por webhook (misma deuda que Ticket; aplica si `conductor_id` no es null). Pendiente para Fase 9.
- **`NegarPago` (Art. 17.g) sin cargo**: infracción registrable con `monto_multa = 0`. Evaluar con el GAD si requiere derivarse a acción administrativa/penal separada.
- **Reporte de Recaudación sin filtro por agente/PV**: `tickets` no tiene `agente_id` ni `punto_venta_id` directos; el filtro por agente requeriría join via `sesiones_parqueo`. Pendiente de refinamiento si el GAD lo solicita.
- **`LiquidacionPuntoVenta.monto_bruto` siempre 0**: la tabla `tickets` no tiene `punto_venta_id` directo; `LiquidacionService::calcularPorPuntoVenta` registra 0 hasta implementar ese campo. Ver `LiquidacionService.php`.
- **Comando `simetsa:verificar-vencimientos-ordenes`**: envuelve `OrdenPagoService::verificarVencimientos()` para marcar órdenes vencidas diariamente. Pendiente de agregar al scheduler en `routes/console.php`.

---

## Decisiones cerradas

- Pasarela de pagos: **Deuna** (multi-proveedor vía PaymentProviderInterface; stub/fake en Fase 6).
- Push: **Firebase Cloud Messaging**.
- Mapas: **OpenStreetMap + Leaflet** (web) / **react-native-maps OSM** (móvil).
- Almacenamiento: disco local `public`.
- Comprobantes: nota de venta interna, preparado para facturación electrónica SRI a futuro.

---

## Reglas críticas para la app móvil

- **Usuarios con múltiples roles**: el sistema debe soportar que un usuario tenga `conductor` + `agente_parqueo` simultáneamente. Es el caso de prueba obligatorio antes de cerrar cualquier fase de la app móvil.
- **Rol activo vs roles disponibles**: la app tiene `activeRole` (modo actual) y `usuario.roles[]` (todos los roles). Nunca asumir que el primer elemento del array es el único rol.
- **Flujos críticos de aceptación**: "comprar ticket" y "cancelar ticket" deben verificarse en modo conductor. "Validar placa" y "registrar infracción" deben verificarse en modo agente.
- **No cerrar una fase sin verificar el flujo en app móvil** cuando el cambio involucra endpoints que consume la app.
- **Permisos por acción en backend**: la autorización real ocurre en el backend (`permission:` middleware); la visibilidad en la app no es suficiente.

---

## Metodología y formato de respuesta

- Trabajamos por **fases incrementales**: no avanzar a la siguiente hasta que la actual esté completa, probada y aprobada.
- Antes de generar código: **proponer estructura → comandos artisan combinados → esperar confirmación → generar código completo → sugerir pruebas**.
- Bloques de código con **path completo** como comentario inicial. Comandos artisan agrupados en un solo bloque bash. Resumen final con archivos creados, comandos a ejecutar, próximos pasos.
- No saltar fases ni adelantar funcionalidades sin pedido. No introducir paquetes/CSS distintos a los acordados. No modificar archivos base de Laravel sin avisar.
- Mantener vivo este archivo: actualizar **Estado actual** y **Deuda técnica** al cierre de cada sub-fase.