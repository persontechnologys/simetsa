# API de Fiscalización — Turnos, Recorridos e Incidentes

> Fase 9.5.4 — Art. 38 Ordenanza SIMETSA

## Endpoints

| Método | URL | Permiso | Descripción |
|--------|-----|---------|-------------|
| `GET`  | `/api/v1/turnos/activo` | `turnos.ver` | Turno activo del agente autenticado |
| `POST` | `/api/v1/turnos` | `turnos.iniciar` | Iniciar nuevo turno |
| `PATCH`| `/api/v1/turnos/{id}/finalizar` | `turnos.iniciar` | Finalizar turno activo |
| `POST` | `/api/v1/recorridos` | `turnos.iniciar` | Registrar posición GPS |
| `POST` | `/api/v1/incidentes` | `incidentes.registrar` | Registrar incidente + notificar ECU 911 |

---

## Curls de referencia

```bash
# Login del agente (obtener token)
curl -X POST ${APP_URL}/api/v1/movil/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"agente@simetsa.gob.ec","password":"password"}'
# → Copiar "token" del JSON de respuesta

# GET — turno activo del agente
curl ${APP_URL}/api/v1/turnos/activo \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Accept: application/json"
# → { "exito": true, "datos": { turno } | null }

# POST — iniciar turno
curl -X POST ${APP_URL}/api/v1/turnos \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"observaciones":"Inicio jornada mañana"}'
# → 201 { "exito": true, "datos": { "id":1, "estado":"iniciado", "inicio_at":"..." } }

# PATCH — finalizar turno
curl -X PATCH ${APP_URL}/api/v1/turnos/1/finalizar \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"observaciones":"Jornada sin novedades."}'
# → 200 { "exito": true, "datos": { "estado":"finalizado", "fin_at":"..." } }

# POST — registrar posición GPS
curl -X POST ${APP_URL}/api/v1/recorridos \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"latitud":-1.047412,"longitud":-78.581903}'
# → 201 { "exito": true, "datos": { "id":1, "latitud":-1.047412, ... } }

# POST — registrar incidente
curl -X POST ${APP_URL}/api/v1/incidentes \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"tipo":"accidente","descripcion":"Colisión leve en Av. 19 de Mayo","latitud":-1.047412,"longitud":-78.581903}'
# → 201 { "exito": true, "datos": { "tipo":"accidente", "reportado_ecu911":true, "notificado_at":"..." } }

# Error: 422 — ya tiene turno activo
curl -X POST ${APP_URL}/api/v1/turnos \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Accept: application/json" \
  -d '{}'
# → 422 { "exito": false, "mensaje": "Ya tenés un turno activo..." }

# Error: 401 — sin token
curl ${APP_URL}/api/v1/turnos/activo \
  -H "Accept: application/json"
# → 401 Unauthenticated

# Error: 403 — conductor intenta iniciar turno
curl -X POST ${APP_URL}/api/v1/turnos \
  -H "Authorization: Bearer TOKEN_CONDUCTOR" \
  -H "Accept: application/json"
# → 403 Forbidden
```

---

## Tipos de incidente

| Valor | Descripción |
|-------|-------------|
| `accidente`  | Accidente de tránsito |
| `obstruccion`| Obstrucción vial |
| `conflicto`  | Conflicto con conductor |
| `otro`       | Otro |

## Notas

- El endpoint `GET /turnos/activo` devuelve `datos: null` si el agente no tiene turno activo (no es un error).
- `POST /recorridos` y `POST /incidentes` requieren un turno activo; si no hay uno, retornan 422.
- El stub ECU 911 siempre retorna `reportado_ecu911: true` — no hace llamadas HTTP reales. En Fase 10 se reemplaza por integración real.
- Las fotos de evidencia se envían como `multipart/form-data` con el campo `foto_evidencia` (JPEG/PNG/WebP, máx 5 MB).
