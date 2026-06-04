# Autenticación Móvil Global — Expo SDK 56

Documento de referencia para la arquitectura de autenticación de la app
`simetsa-movil`. Ver también: `docs/api/movil-auth.md` para los endpoints backend.

---

## AuthContext (`src/context/AuthContext.js`)

Contexto React que gestiona la sesión completa. Envuelve toda la app en
`app/_layout.js`.

### Valores expuestos

```javascript
const {
  token,          // string | null — Bearer token activo
  usuario,        // { id, nombre, email, roles[], permisos[] } | null
  cargando,       // boolean — true mientras se recupera la sesión del storage
  perfil,         // objeto ConductorResource | AgenteParqueoResource | null
  rol,            // string | null — primer rol del usuario (backward compat)
  guardarSesion,  // fn — persiste sesión post-login o post-registro
  cerrarSesion,   // fn — elimina sesión y revoca token en backend
  hasRole,        // fn(rol: string) → boolean
  hasAnyRole,     // fn(roles: string[]) → boolean
  can,            // fn(permiso: string) → boolean
} = useAuth();
```

### Helpers de autorización

```javascript
// Verificar un rol exacto
if (hasRole('conductor')) { /* mostrar tabs conductor */ }

// Verificar cualquiera de varios roles
if (hasAnyRole(['conductor', 'agente_parqueo'])) { /* acceso común */ }

// Verificar un permiso Spatie
if (can('tickets.comprar')) { /* mostrar botón comprar */ }
```

Los roles y permisos vienen del backend en cada login via `UsuarioMovilResource`.
Se almacenan en `SecureStore` y están disponibles offline sin consultar el backend.

---

## Flujo de login

```
login.js (formulario email+password)
  ↓
authService.login({ email, password })
  POST /api/v1/movil/login
  ↓ respuesta: { token, usuario: { roles[], permisos[] }, perfil_operativo }
  ↓
obtenerTokenPush()  ← best-effort, null en Expo Go
  ↓
guardarSesion({ token, usuario, perfil_operativo }, fcmToken)
  ↓ SecureStore: USUARIO, PERFIL_OP, TOKEN, ROL (legacy), PERFIL (legacy)
  ↓ axios: Authorization = Bearer {token}
  ↓
registrarToken({ token: fcmToken, ... })  ← solo si fcmToken != null
  ↓
router.replace('/')
  ↓
app/index.js → hasRole('conductor') → /(conductor)/(tabs)
             → hasRole('agente_parqueo') → /(agente)/(tabs)
```

**FCM es completamente opcional.** El login no depende de push. En Expo Go,
`obtenerTokenPush()` devuelve `null` sin lanzar error.

---

## Flujo de registro (conductor)

```
registro.js → registrarConductor(datos) → POST /auth/registro
  ↓ respuesta: { token, conductor } — sin roles[], sin permisos[]
  ↓
apiClient.defaults.headers.common['Authorization'] = Bearer {token}
  ↓
me() → GET /api/v1/movil/me
  ↓ respuesta completa: { usuario: { roles[], permisos[] }, perfil_operativo }
  ↓ si falla: fallback con roles: ['conductor'], permisos: []
  ↓
guardarSesion({ token, ...sesionCompleta }, null)
  ↓
router.replace('/') → app/index.js → /(conductor)/(tabs)
```

---

## Redirección post-login (`app/index.js`)

```javascript
if (!token) {
  router.replace('/(auth)/login');
} else if (hasRole(ROLES.CONDUCTOR)) {
  router.replace('/(conductor)');   // → (conductor)/(tabs)/index.js
} else if (hasRole(ROLES.AGENTE)) {
  router.replace('/(agente)');      // → (agente)/(tabs)/index.js
} else {
  router.replace('/(auth)/login');  // token válido pero rol no móvil
}
```

Los archivos `(conductor)/index.js` y `(agente)/index.js` de nivel raíz NO existen
intencionalmente: Expo Router cae al tab navigator `(tabs)/` como pantalla por defecto.

---

## Almacenamiento de sesión

| Key | Contenido | Tipo |
|-----|-----------|------|
| `simetsa_token` | Bearer token | string |
| `simetsa_usuario` | `{ id, nombre, email, roles[], permisos[] }` | JSON |
| `simetsa_perfil_op` | ConductorResource o AgenteParqueoResource | JSON |
| `simetsa_rol` | primer rol (legacy backward compat) | string |
| `simetsa_perfil` | perfil legacy (backward compat) | JSON |
| `simetsa_fcm_token` | token FCM si disponible | string |

Al iniciar la app, `AuthContext` recupera estas keys y restaura la sesión sin
re-login.

### Migración de sesiones legacy

Si solo existen las keys legacy (`simetsa_rol`, `simetsa_perfil`), el contexto
reconstruye un usuario mínimo con `roles: [rol]` y `permisos: []`. El usuario
verá las pantallas pero `can()` devolverá `false` hasta que haga login nuevamente.

---

## Cierre de sesión

```javascript
cerrarSesion();
// 1. Llama POST /api/v1/movil/logout (best-effort, no bloquea si falla)
// 2. Elimina todas las keys de SecureStore
// 3. Elimina header Authorization de axios
// 4. Redirige a /(auth)/login
```

---

## Agregar un nuevo rol móvil

**Backend** (`config/simetsa.php`):
```php
'roles_movil' => [
    RolSistema::Conductor->value,
    RolSistema::AgenteParqueo->value,
    RolSistema::PuntoVenta->value,  // nuevo
],
```

Luego agregar `case` en `MovilAuthController::resolverPerfilOperativo()` y el
método privado correspondiente.

**Mobile** (`src/constants/config.js`):
```javascript
ROLES = {
  ...
  PUNTO_VENTA: 'punto_venta',  // nuevo
};
ROLES_MOVIL = [ROLES.CONDUCTOR, ROLES.AGENTE, ROLES.PUNTO_VENTA];
```

Luego agregar la lógica de redirección en `app/index.js`.

---

## FCM y notificaciones push (Fase 9.F)

Push real requiere **development build** o **production build**. No funciona en
Expo Go (el token es `null`).

- `src/utils/notificaciones.js → obtenerTokenPush()` — nunca lanza error.
- `src/services/dispositivosService.js → registrarToken()` — llama
  `POST /api/v1/dispositivos` para registrar el token FCM en backend.
- La app funciona completamente sin push. Las notificaciones son un enhancement.
