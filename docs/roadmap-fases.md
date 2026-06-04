# Roadmap de fases

Trabajamos por **fases incrementales**. No avanzar a la siguiente fase hasta que la actual esté **completa, probada y aprobada**.

> El estado actual y el próximo paso viven en el `CLAUDE.md` raíz; acá está el detalle de cada fase.

---

## Fase 0 — Configuración inicial ✓

- Proyecto Laravel 11 creado (`pry_simetsa`).
- Breeze, Sanctum y Spatie Permission instalados.
- PostgreSQL configurado.

## Fase 1 — Roles, permisos y usuarios del sistema ✓

- Roles definidos: `super_admin`, `comisario`, `director_seguridad`, `agente_parqueo`, `punto_venta`, `conductor`.
- Permisos por módulo (`config/simetsa_permisos.php`).
- Modelo `PerfilUsuario` (extensión 1:1 del `User`).
- Seeder con usuarios de prueba para cada rol.
- Middleware `perfil.completo` para gateo de rutas operativas.
- Vistas Blade de gestión de usuarios y roles.

## Fase 2 — Catálogos base ✓

- Zonas tarifadas, calles, manzanas, plazas, tipos de plaza.
- Tarifas, horarios de operación, días feriados.
- Tabla de parámetros globales (SBU, tolerancia, tiempo máximo).
- CRUDs completos en backoffice con mapas Leaflet.

## Fase 3 — Agentes de Parqueo y Puntos de Venta ✓

**3.A** Solicitud + Documentación (Etapa 1, Art. 32-34): `SolicitudAgente`, `DocumentoAgente`, `SolicitudAgenteService`.
**3.B** Capacitación (Etapa 2, Art. 33.5): `CursoCapacitacion`, `InscripcionCurso`, `CalificacionCurso`, `CapacitacionService` (promedio ≥ 70).
**3.C** Autorización + Agente activo (Etapa 3, Art. 36): `AgenteParqueo`, `ExpedienteAgente`, `AgenteParqueoService` (crea cuenta con rol y perfil).
**3.D** Operación: `AsignacionZona` (Art. 16), `HorarioRotativo` (Art. 37.4), `AmonestacionAgente` (Art. 40, escalada verbal → escrita → terminación). Edición vía modales compartidos. Reglas relajadas: solo bloquear duplicado exacto.
**3.E.1** Solicitud de Punto de Venta + Documentación (Art. 31): `SolicitudPuntoVenta`, `DocumentoPuntoVenta`.
**3.E.2** Contrato + Punto de Venta activo (Art. 31 / 21): `ContratoPuntoVenta`, `PuntoVenta`, `PuntoVentaService` (resolución de identidad cédula → correo → crear, regla 3 cuadras, descuento 10%).

## Fase 4 — Conductores y Vehículos (API móvil) ✓

**Autenticación:** `AuthController` (registro público, login, logout, perfil), `ConductorService::registrar()` (crea `User` + rol `conductor` + `PerfilUsuario` + `Conductor` en una transacción). Tokenización vía Sanctum. Consentimiento LOPDP al registrar (Art. 7 LOPDP). `AutenticacionConductorTest` (9 tests).

**4.A** Catálogo `TipoVehiculo` (Art. 25): migración `tipos_vehiculo`, 6 tipos semilla (`liviano_privado`, `liviano_publico`, `taxi`, `furgoneta`, `carga_liviana`, `institucional`). CRUD backoffice (director/admin); endpoint API read-only `GET /api/v1/tipos-vehiculo` accesible a cualquier usuario autenticado. `TipoVehiculoControllerTest` (9 tests).

**4.B** Vehículos del conductor (Art. 25): `Vehiculo` + `VehiculoService` (`registrar`, `actualizar`, `eliminar`, `cambiarEstado`). Placa normalizada a mayúsculas; unicidad entre no-eliminados vía índice parcial PostgreSQL (`WHERE deleted_at IS NULL`). API REST completa (`apiResource /vehiculos`) con ownership: el conductor solo accede a sus propios vehículos (`VehiculoPolicy`). `VehiculoApiTest` (12 tests).

**4.C** Credencial CONADIS (Art. 26): `CredencialDiscapacidad` + `CredencialDiscapacidadService` (`solicitar`, `aprobar`, `rechazar`). Un vehículo solo puede tener una credencial activa (`pendiente`|`aprobada`) a la vez. El conductor la solicita desde la app; comisario/director la aprueba en el backoffice (`PATCH /credenciales-discapacidad/{id}/aprobar|rechazar`). Adjunto PDF/imagen en disco `public`. `CredencialDiscapacidadApiTest` (13 tests).

**4.D** Backoffice supervisión y exoneraciones (Art. 27, Art. 37): `ConductorController` (listado + detalle + bloquear/desbloquear, Art. 37); `VehiculoExonerado` + `VehiculoExoneradoController` (CRUD completo; sin FK a `vehiculos` — son vehículos institucionales: Policía, Bomberos, FF.AA., Municipal; tiempo máximo 2 horas, Art. 27). `ConductorService::cambiarEstado()`. Vistas Blade: `conductores/{index,show}`, `vehiculos-exonerados/{index,create,edit}`. `ConductorControllerTest` (8 tests), `VehiculoExoneradoControllerTest` (8 tests).

## Fase 5 — Sistema de Tickets Digitales ✓

**Decisiones de diseño:** `EstadoTicket` como `BackedEnum` PHP 8.2; `SesionParqueo` tabla separada 1:1 con `Ticket`; `Cancelacion` unifica baja-conductor y anulación-admin con discriminador `tipo` enum; `zona_id` obligatorio + `calle_id` opcional en ticket; fallback $0.25/hora (Art. 22) si sin tarifa vigente; cruce de jornada rechazado con mensaje orientativo.

**5.A** Modelos, migraciones y enums: `Ticket`, `SesionParqueo`, `Cancelacion`, `DispositivoMovil`, `NotificacionPush`. Enums: `EstadoTicket`, `EstadoSesionParqueo`, `MetodoPago`, `TipoCancelacion`. Policies: `TicketPolicy` (ownership conductor), `SesionParqueoPolicy`.

**5.B** `TicketService` con todas las reglas de la Ordenanza: `comprar`, `calcularMonto`, `validarHorarioYFeriado`, `validarMaximoHoras`, `validarPorPlaca` (tolerancia Art. 13), `cancelar`, `anular`. 21 tests de borde (Arts. 12, 13, 14, 22, 26, 27).

**5.C** API móvil conductor: `GET /api/v1/tickets` (vigentes), `POST /api/v1/tickets` (comprar), `GET /api/v1/tickets/historial`, `GET /api/v1/tickets/{id}`, `POST /api/v1/tickets/{id}/cancelar`. `TicketResource`, `SesionParqueoResource`. 15 tests. `docs/api/tickets.md`.

**5.D** API agente en calle: `GET /api/v1/tickets/validar/{placa}` (estado + tolerancia), `POST /api/v1/sesiones-parqueo` (iniciar), `GET /api/v1/sesiones-parqueo/{id}`. `SesionParqueoService`. 13 tests. `docs/api/sesiones.md`.

**5.E–5.F** Backoffice supervisión y anulación: `TicketController` web (index + show + anular), vistas `tickets/{index,show}.blade.php`, modal de anulación. Acceso por rol (super_admin|comisario|director_seguridad). 9 tests.

**5.G** FCM placeholder: `POST /api/v1/dispositivos` (registrar/actualizar token, idempotente), `DELETE /api/v1/dispositivos/{token}`. `NotificacionPushService::encolar()` / `marcarEnviada()`. 10 tests. `docs/api/dispositivos.md`.

**Total Fase 5:** 68 tests, 7 commits, 3 docs de API.

## Fase 6 — Pagos multi-proveedor + FCM real ✓

**6.0** Cleanup: MetodoPago → dos campos (`metodo_pago` + `proveedor`), enum `ProveedorPago`, gate `PagoSimulado` por entorno, comando `simetsa:sincronizar-estados-tickets` (deuda técnica #5 resuelta), eliminación de PayPhone.

**6.A** Arquitectura multi-proveedor: interfaces `Cobrable` + `PaymentProviderInterface`, modelo `TransaccionPago` (polimórfico, softDeletes), `DeunaPaymentProvider` en modo fake (sin HTTP externo), `PagoManager` singleton. `Http::assertNothingSent()` garantiza seguridad.

**6.B** FCM real: `kreait/laravel-firebase` (^7.2), `EnviarNotificacionFCMJob` (tries=3, backoff exponencial, lazy FCMService), interruptor `FCM_ENABLED`, columnas `fallida_en/ultimo_error/omitida` en `notificaciones_push`.

**6.C** Integración: `EstadoTicket::PendientePago`, `EstadoReembolso` en `Cancelacion`, `PagoWebhookController` (POST `/api/v1/pagos/webhook/{proveedor}`, público, idempotente), `Ticket::acreditar()` transiciona estado.

**Total Fase 6:** 35 tests nuevos, 393 passing. Decisiones: proveedor explícito en request, webhook registrado desde inicio (fake acepta cualquier firma), kreait/laravel-firebase sobre firebase-php puro, `calcularEstadoActual()` público en `TicketService`.

**Pendiente para producción:** credenciales Deuna reales (`DEUNA_ENABLED=true`), credencial Firebase (`FIREBASE_CREDENTIALS`), reembolso automático vía Deuna (requiere endpoint oficial).

## Fase 7 — Infracciones e Inmovilización ✓

**7.A** Modelos y fundación: `Infraccion` + `Inmovilizacion` (1:1 nullable, Art. 15). Enums: `TipoInfraccion` (12 casos, Arts. 17+18), `EstadoInfraccion` (pendiente → pagada/anulada), `EstadoInmovilizacion` (activa → liberada/anulada). `Infraccion implements Cobrable` (morph `concepto`, integración Fase 6). Policies: agente solo ve las suyas; comisario/director bypass. Factories con states `tiempoExcedido`, `pagada`, `liberada`.

**7.B** `InfraccionService`: `calcularMulta()` (tabla escalonada Art. 28: 2/4/8%; fijos Art. 29: 2/20%; agresión Art. 30: 50%; NegarPago: 0), `registrar()` (snapshot SBU, normalización placa), `inmovilizar()`, `liberar()` (Art. 15: pago previo o motivo administrativo), `anular()` (cascada sobre inmovilización activa). 27 tests de borde.

**7.C** API agente en calle: `POST /api/v1/infracciones` (registrar + multa automática), `GET /api/v1/infracciones/{id}` (detalle con inmovilización), `POST /{id}/inmovilizar` (Art. 15), `POST /{id}/liberar`. Permisos: `infracciones.registrar`, `inmovilizaciones.aplicar`, `inmovilizaciones.retirar` (agregados al rol `agente_parqueo`). `docs/api/infracciones.md`. 14 tests.

**7.D** API conductor: `GET /api/v1/conductor/infracciones` (historial por placa de vehículos + conductor_id), `POST /api/v1/infracciones/{id}/pagar` (inicia cobro vía `PagoManager`). Webhook genérico existente (`PagoWebhookController`) acredita el pago sobre `Infraccion` sin cambios → llama `acreditar()` → estado `pagada` + inmovilización `liberada` (Art. 15 end-to-end). 11 tests.

**7.E** Backoffice supervisión: `InfraccionController` web (index con 7 filtros, show con inmovilización + transacciones embebidas, anular con modal). Vistas Blade: `infracciones/index.blade.php`, `infracciones/show.blade.php`. Acceso: super_admin/comisario/director. `AnularInfraccionRequest`. Rutas + breadcrumbs. 10 tests.

**Total Fase 7:** 80 tests nuevos, 473 passing. 2 commits (`daf8b1f` código + `3b69086` limpieza). Decisiones: `TipoInfraccion` BackedEnum PHP 8.2 (catálogo cerrado por Ordenanza), `Inmovilizacion` entidad propia (agente puede diferir del que registra la infracción), `monto_multa` persistido con snapshot `sbu_vigente`, `NegarPago` (Art. 17.g) registrable sin cargo económico, `conductor_id` nullable en `Infraccion`. Commit de limpieza: `docs/api/infracciones.md` con endpoints conductor documentados (GET /conductor/infracciones, POST /{id}/pagar), `InmovilizacionSeeder` implementado, `InmovilizacionController` web stub eliminado.

## Fase 8 — Reportes y Dashboard ✓

**8.A Dashboard KPIs:** Vista principal (`GET /dashboard`) con 6 tarjetas (tickets activos, recaudación hoy/mes, infracciones pendientes, plazas ocupadas, agentes activos) + 3 gráficos Chart.js (línea recaudación 30 días, barras por zona, doughnut método de pago). Endpoint JSON `GET /dashboard/kpis` para polling AJAX cada 60 s. Cache Laravel 5 min. Dashboard accesible a todos los roles; KPIs visibles solo con permiso `kpi.ver` (super_admin, comisario, director_seguridad). `DashboardController`, `ReporteService::kpis()`. 15 tests.

**8.B Reporte de Recaudación:** Vista `GET /reportes/recaudacion` con 4 tarjetas resumen + filtros (fecha desde/hasta, tipo ticket|infracción, proveedor, zona) + tabla paginada 50/página + link "Ver detalle". Exportación: `GET /reportes/recaudacion/excel` (Maatwebsite `.xlsx`, 8 columnas, headers en bold) y `GET /reportes/recaudacion/pdf` (Blade imprimible via `layouts.impresion`). Paquete instalado: `maatwebsite/excel ^3.1`. `RecaudacionController`, `ReporteService::recaudacion()`, `RecaudacionExport`. Acceso: `reportes.ver`; exportación: `reportes.exportar`. 15 tests.

**8.C Reporte de Infracciones:** Vista `GET /reportes/infracciones` con 5 tarjetas (total, cobrado, pendiente, inmovilizadas activas) + filtros (fecha, estado, tipo, zona, agente) + tabla con badge de estado + badge de inmovilización + link a detalle de infracción. Excel + PDF vía misma infraestructura de 8.B. `InfraccionesController`, `ReporteService::infracciones()`, `InfraccionesExport`. 16 tests.

**8.D Reporte de Ocupación:** Vista `GET /reportes/ocupacion` con 4 tarjetas (sesiones, duración promedio en min, hora pico, ocupadas ahora) + 3 gráficos Chart.js (barras por día, barras por hora del día 0-23, doughnut por zona). Filtros: rango de fechas + zona. Query con `EXTRACT(HOUR FROM inicio_at)` PostgreSQL. `OcupacionController`, `ReporteService::ocupacion()`. 9 tests.

**Total Fase 8:** 56 tests nuevos, 529 total. Paquete nuevo: `maatwebsite/excel ^3.1`. Layout nuevo: `resources/views/layouts/impresion.blade.php`. Partial nuevo: `resources/views/reportes/_partials/kpi-card.blade.php` (reutilizado en los 4 reportes). Decisiones: Maatwebsite para Excel + Blade imprimible para PDF (sin `wkhtmltopdf`), Cache Laravel 5 min para KPIs, `whereHasMorph` para filtro zona en recaudación, `JSON_PRESERVE_ZERO_FRACTION` en endpoint kpis, `ReporteGenerado`/`KPI` como modelos descartados (queries directas + cache suficientes).

## Fase 9 — Aplicación Móvil (Expo) ✓

Stack: **Expo SDK 56** (JS puro, sin TypeScript) + `react-native-maps` (OSM) + `expo-location` + `expo-secure-store`. Repositorio: `/workspace/simetsa-movil`. Rama backend: `fase-9-app-movil`.

**9.A ✓ Scaffolding** — Estructura Expo Router file-based, una sola app para conductor y agente (no dos apps separadas). Componentes base: `BotonPrimario`, `CampoTexto`, `Cargando`, `EtiquetaEstado`, `MensajeError`. Servicios: `authService`, `apiClient` (axios), `ticketsService`, `vehiculosService`, `infraccionesService`, `sesionesService`, `zonasService`, `dispositivosService`.

**9.B ✓ Auth global unificada** — `MovilAuthController` (login/logout/me): un solo endpoint `POST /api/v1/movil/login` detecta el rol automáticamente. `UsuarioMovilResource` devuelve `roles[]` + `permisos[]`. `config/simetsa.php → roles_movil` define qué roles pueden acceder. `AuthContext` (Expo) expone `hasRole()`, `hasAnyRole()`, `can()`. Token Bearer en `expo-secure-store`. FCM completamente opcional (no bloquea login). Redirección post-login basada en roles. Backward compat con endpoints legacy. 8 tests nuevos, 544 total. Docs: `docs/api/movil-auth.md`, `docs/mobile/auth-global.md`.

**9.C ✓ App Conductor** — 5 tabs: Inicio (KPIs: tickets activos, multas pendientes; botón Comprar Ticket; últimos tickets), Tickets (listado activos), Vehículos (CRUD), Multas (infracciones pendientes e inmovilizadas), Perfil (cierre de sesión). Pantallas adicionales: comprar-ticket (modal, selector zona/calle/vehículo/horas/método pago), historial, detalle-ticket, detalle-vehículo, detalle-infracción, crear-vehículo.

**9.D ✓ App Agente** — 5 tabs: Inicio (tarjeta agente nombre/código/estado + 3 acciones rápidas), Validar Placa (ABC-1234, muestra ticket activo/tolerancia/sin ticket, botón "Iniciar Sesión de Parqueo" inline Art. 16), Infracción (form: placa, tipo 12 opciones, zona, GPS, observaciones; opción inmovilizar post-registro), Mapa (OSM sin API key, polígono zona, GPS real-time), Perfil. Pantalla detalle-infracción en stack.

**9.E ✓ Mapa agente** — `react-native-maps` con tiles OSM (no API key), polígono de zona desde `AgenteParqueoResource.zona_actual.poligono`, posición GPS real-time via `expo-location watchPositionAsync`, FAB centrar, atribución OSM.

**9.F ✓ FCM directo** — `getDevicePushTokenAsync()` + metadatos (`canal`, `tipo_app`, `modelo`) en tabla `dispositivos_moviles`. Requiere **development build** para obtener token real (null en Expo Go). Lazy import de `expo-notifications` para evitar crash en Expo Go.

**9.G ✓ Pulido e integración final** — 8 stubs eliminados (routing limpio), `pago_simulado` gateado con `__DEV__` en comprar-ticket y detalle-infraccion, `EXPO_PUBLIC_API_URL` via `.env` (Expo SDK 56 env vars nativas), `.env.example` documentado, `eas.json` con perfiles development/preview/production. 21/21 expo-doctor.

**Decisiones:**
- Una sola app (no dos APKs): menú y tabs se muestran según rol.
- `(conductor)/index.js` y `(agente)/index.js` de nivel raíz eliminados: Expo Router cae al tab navigator directamente.
- `sesion-parqueo.js` eliminado: el inicio de sesión es inline en `validar-placa.js` (botón "Iniciar Sesión de Parqueo" con `iniciarSesion({ ticket_id })`).
- Token almacenado en `expo-secure-store`; header Bearer aplicado globalmente en axios.
- FCM best-effort: el login no depende de push.

## Fase 10 — Integraciones Externas

- CONADIS (validación de discapacidad).
- ANT (validación de placas).
- ECU 911 (incidentes — Art. 38.m).
- Tesorería Municipal (cobros y conciliación).

## Fase 11 — Despliegue y Puesta en Producción

- Configuración del servidor (a definir según el GAD).
- Hardening, SSL, backups.
- Capacitación al personal del GAD.