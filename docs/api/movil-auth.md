# API Autenticación Móvil Unificada — Fase 9

Endpoint único `/api/v1/movil/*` para todos los roles habilitados en la app móvil
(conductor y agente_parqueo). El backend detecta el rol automáticamente a partir de
las credenciales y devuelve `roles[]`, `permisos[]` y `perfil_operativo` en una sola
llamada.

Roles habilitados para móvil: definidos en `config/simetsa.php → roles_movil`.
Para agregar un nuevo rol, añadirlo ahí y crear el método `perfil{Rol}()` en
`MovilAuthController`.

---

## Endpoints

### POST /api/v1/movil/login

Autenticación unificada. No requiere que el cliente sepa el rol del usuario de antemano.

**Auth:** pública

**Request:**
```json
{
  "email": "conductor@simetsa.gob.ec",
  "password": "password"
}
```

**Response 200:**
```json
{
  "exito": true,
  "mensaje": "Sesión iniciada.",
  "datos": {
    "token": "1|aBcDeFgHiJ...",
    "tipo_token": "Bearer",
    "usuario": {
      "id": 3,
      "nombre": "Juan Pérez",
      "email": "conductor@simetsa.gob.ec",
      "roles": ["conductor"],
      "permisos": [
        "tickets.comprar",
        "tickets.ver",
        "tickets.cancelar",
        "vehiculos.crear",
        "vehiculos.ver",
        "vehiculos.editar",
        "vehiculos.eliminar",
        "dispositivos_moviles.registrar"
      ]
    },
    "perfil_operativo": {
      "id": 1,
      "codigo": "COND-0001",
      "estado": "activo",
      "nombre": "Juan Pérez",
      "email": "conductor@simetsa.gob.ec",
      "cedula": "1710034065",
      "telefono_celular": "0987654321",
      "fecha_registro": "2026-01-15",
      "total_vehiculos": 2
    }
  },
  "errores": null
}
```

Para un **agente**, `perfil_operativo` tiene la estructura de `AgenteParqueoResource`:
```json
{
  "id": 1,
  "codigo": "AG-0001",
  "estado": "activo",
  "nombre": "Carlos Mendez",
  "email": "agente@simetsa.gob.ec",
  "numero_credencial": "CRED-001",
  "fecha_autorizacion": "2026-01-10",
  "zona_actual": {
    "id": 1,
    "nombre": "Centro",
    "codigo": "Z001",
    "color": "#1B4F72",
    "centro_lat": -1.0045,
    "centro_lng": -78.5936,
    "zoom": 16,
    "poligono": [[-1.003, -78.594], ...]
  }
}
```

**Errores:**

| Código | Causa |
|--------|-------|
| 401 | Credenciales inválidas |
| 403 | Rol no habilitado para la app móvil |
| 403 | Perfil inactivo o no encontrado |
| 422 | Validación (email o password faltantes) |

---

### POST /api/v1/movil/logout

Revoca el token Bearer activo.

**Auth:** `Bearer {token}`

**Request:** sin body

**Response 200:**
```json
{
  "exito": true,
  "mensaje": "Sesión cerrada correctamente.",
  "datos": null,
  "errores": null
}
```

---

### GET /api/v1/movil/me

Devuelve el usuario y perfil operativo del token activo. Útil post-registro de
conductor para obtener `roles[]` y `permisos[]` que no vienen en el endpoint
`POST /auth/registro`.

**Auth:** `Bearer {token}`

**Response 200:** estructura idéntica a `POST /movil/login → datos`.

**Errores:**

| Código | Causa |
|--------|-------|
| 401 | Token inválido, expirado o revocado |
| 403 | Rol no habilitado para la app móvil |
| 403 | Perfil inactivo o no encontrado |

---

## Endpoints legacy (se mantienen)

Los siguientes endpoints siguen activos para backward compatibility y sus tests no
han cambiado:

| Endpoint | Rol | Nota |
|----------|-----|------|
| `POST /api/v1/login` | conductor | `AuthController@login` — Fase 4 |
| `POST /api/v1/logout` | conductor | `AuthController@logout` |
| `GET /api/v1/perfil` | conductor | `AuthController@perfil` |
| `POST /api/v1/agente/auth/login` | agente | `AgenteAuthController@login` — Fase 9.B |
| `POST /api/v1/agente/auth/logout` | agente | `AgenteAuthController@logout` |
| `GET /api/v1/agente/auth/perfil` | agente | `AgenteAuthController@perfil` |

La diferencia con el endpoint unificado: los legacy no devuelven `roles[]` ni
`permisos[]` en el usuario — devuelven el recurso del conductor/agente directamente.

---

## Configuración de roles móviles

`config/simetsa.php`:
```php
return [
    'roles_movil' => [
        RolSistema::Conductor->value,     // 'conductor'
        RolSistema::AgenteParqueo->value, // 'agente_parqueo'
    ],
];
```

Para agregar un rol (ejemplo: `punto_venta`):
1. Añadir `RolSistema::PuntoVenta->value` al array.
2. Añadir un `case` en `MovilAuthController::resolverPerfilOperativo()`.
3. Crear el método privado `perfilPuntoVenta(User $user)`.

---

## Notas de seguridad

- Token Sanctum Bearer — nombre `'movil-v2'` en `personal_access_tokens`.
- En el cliente (Expo): token almacenado en `expo-secure-store`.
- El header `Authorization: Bearer {token}` se inyecta globalmente en axios al
  persistir la sesión en `AuthContext`.
- **FCM no bloquea el login**: el registro del token de push es best-effort y se
  ejecuta después de `guardarSesion()`. Si falla (Expo Go, sin Google Play Services),
  el login continúa normalmente.
- Logout revoca el token específico via `PersonalAccessToken::findToken()` y elimina
  todos los tokens del usuario como medida de limpieza.
