# Auditoría App Móvil — Soporte Multi-Rol

> Fecha: 2026-06-06  
> Proyecto: `/workspace/simetsa-movil`  
> Objetivo: Diagnóstico del bug de usuarios con múltiples roles + propuesta de solución

---

## 1. Framework y stack detectado

| Campo | Valor |
|-------|-------|
| Framework | **Expo ~56.0.8** (SDK 56) |
| Router | **Expo Router ~56.2.8** (file-based routing) |
| React Native | 0.85.3 |
| React | 19.2.3 |
| Lenguaje | **JavaScript puro (sin TypeScript)** |
| Cliente HTTP | axios con interceptor global |
| Almacenamiento seguro | `expo-secure-store` |
| Mapas | `react-native-maps` (tiles OSM) |
| Notificaciones | `expo-notifications` (lazy import, null en Expo Go) |

---

## 2. Arquitectura de navegación

Expo Router usa file-based routing. Los grupos de rutas son:

```
app/
├── _layout.js          (RootLayout: AuthProvider + StatusBar)
├── index.js            (Punto de entrada — AQUÍ ESTÁ EL BUG)
├── (auth)/             (Stack: login + registro — sin autenticación)
│   ├── _layout.js
│   ├── login.js
│   └── registro.js
├── (conductor)/        (Stack: tabs conductor)
│   ├── _layout.js
│   ├── (tabs)/
│   │   ├── _layout.js  (Tab navigator — 5 pestañas)
│   │   ├── index.js    (Dashboard)
│   │   ├── tickets.js
│   │   ├── vehiculos.js
│   │   ├── infracciones.js
│   │   └── perfil.js
│   ├── comprar-ticket.js
│   ├── crear-vehiculo.js
│   ├── historial.js
│   ├── detalle-ticket.js
│   ├── detalle-vehiculo.js
│   └── detalle-infraccion.js
└── (agente)/           (Stack: tabs agente)
    ├── _layout.js
    ├── (tabs)/
    │   ├── _layout.js  (Tab navigator — 5 pestañas)
    │   ├── index.js    (Dashboard agente)
    │   ├── validar-placa.js
    │   ├── infraccion.js
    │   ├── mapa.js
    │   └── perfil.js
    └── detalle-infraccion.js
```

---

## 3. Flujo de login actual

```
Usuario ingresa email + password
        ↓
POST /api/v1/movil/login
        ↓
Response: {
  token: "...",
  tipo_token: "Bearer",
  usuario: {
    id: ...,
    nombre: "...",
    email: "...",
    roles: ["conductor"],           ← Array de roles
    permisos: ["tickets.ver", ...]  ← Array de permisos
  },
  perfil_operativo: { ... }         ← ConductorResource o AgenteParqueoResource
}
        ↓
AuthContext almacena en SecureStore:
  - simetsa_token → JWT Bearer
  - simetsa_usuario → { id, nombre, email, roles[], permisos[] }
  - simetsa_perfil_op → perfil_operativo
  - simetsa_rol → roles[0]  ← LEGACY: siempre el primer rol
        ↓
app/index.js evalúa el rol y redirige
```

---

## 4. Cómo se reciben y almacenan los roles desde el backend

### Backend — `MovilAuthController@login` responde:

```json
{
  "usuario": {
    "roles": ["conductor", "agente_parqueo"],
    "permisos": ["tickets.ver", "tickets.comprar", "infracciones.registrar", ...]
  }
}
```

El backend **ya soporta múltiples roles** en la respuesta. El problema es solo del lado del cliente (app móvil).

### App — `src/context/AuthContext.js` almacena:

```javascript
// ✅ Correcto — almacena el array completo
const userData = {
  id: data.usuario.id,
  nombre: data.usuario.nombre,
  email: data.usuario.email,
  roles: data.usuario.roles ?? [],       // Array completo ✅
  permisos: data.usuario.permisos ?? [], // Array completo ✅
};

// ⚠️ Legacy — solo el primer rol
await SecureStore.setItemAsync('simetsa_rol', data.usuario.roles?.[0] ?? '');
```

---

## 5. Cómo se decide qué menú/pantalla mostrar — El bug

### Archivo: `app/index.js` líneas 21-24

```javascript
// CÓDIGO ACTUAL — INCORRECTO para usuarios con múltiples roles:
useEffect(() => {
  if (!cargando) {
    if (!token) {
      router.replace('/(auth)/login');
    } else if (hasRole(ROLES.CONDUCTOR)) {
      router.replace('/(conductor)');    // ← PRIMERA RAMA — siempre gana
    } else if (hasRole(ROLES.AGENTE)) {
      router.replace('/(agente)');       // ← NUNCA se ejecuta si tiene conductor
    } else {
      router.replace('/(auth)/login');
    }
  }
}, [cargando, token]);
```

**El problema:** `hasRole(ROLES.CONDUCTOR)` retorna `true` si `usuario.roles` incluye `'conductor'`. En un usuario con `roles: ['conductor', 'agente_parqueo']`, la primera rama gana siempre.

### Definición de `hasRole` en `src/context/AuthContext.js`:

```javascript
// Líneas 141-143 — Correcta como función, pero el index.js la usa mal
const hasRole = useCallback(
  (r) => usuario?.roles?.includes(r) ?? false,
  [usuario]
);
```

### Campo legacy comprometido:

```javascript
// Backward compat — en pantallas de perfil se usa:
const { perfil, rol } = useAuth(); // rol = roles[0] → SIEMPRE conductor si es el primero
```

---

## 6. Pantallas afectadas por el bug

### Conductor — `app/(conductor)/(tabs)/perfil.js`

```javascript
// Actualemente solo tiene botón de Cerrar Sesión.
// No tiene opción de "Cambiar a modo agente".
```

### Agente — `app/(agente)/(tabs)/perfil.js`

```javascript
// Igual — solo tiene Cerrar Sesión.
```

### AuthContext — sin `activeRole`:

```javascript
// Estado actual del contexto:
const [usuario, setUsuario] = useState(null);
const [token, setToken] = useState(null);
const [perfilOp, setPerfilOp] = useState(null);
// ← FALTA: const [activeRole, setActiveRole] = useState(null);
```

---

## 7. Propuesta de solución técnica

### Principios de diseño:

1. **No romper la sesión existente** — el token permanece igual; solo cambia la vista
2. **Mínimo de cambios** — el backend ya retorna roles correctamente; solo cambia el frontend
3. **Guard por `activeRole`** — los layouts verifican el rol activo, no solo si el usuario tiene el rol
4. **Cambio de modo sin logout** — el usuario cambia de modo desde su perfil

### Cambios requeridos:

#### A. `src/context/AuthContext.js`

Agregar estado `activeRole`:

```javascript
// Agregar al estado del contexto:
const [activeRole, setActiveRoleState] = useState(null);

// Al restaurar sesión desde SecureStore:
const savedActiveRole = await SecureStore.getItemAsync('simetsa_active_role');
if (savedActiveRole) setActiveRoleState(savedActiveRole);

// Función pública para cambiar el rol activo:
const setActiveRole = useCallback(async (nuevoRol) => {
  // Verificar que el usuario tiene ese rol
  if (!usuario?.roles?.includes(nuevoRol)) return false;
  setActiveRoleState(nuevoRol);
  await SecureStore.setItemAsync('simetsa_active_role', nuevoRol);
  return true;
}, [usuario]);

// Al hacer login, el rol activo inicial es el primer rol:
const initialRole = userData.roles?.[0] ?? null;
setActiveRoleState(initialRole);
await SecureStore.setItemAsync('simetsa_active_role', initialRole ?? '');

// Al hacer logout, limpiar el rol activo:
await SecureStore.deleteItemAsync('simetsa_active_role');

// Exponer en el contexto:
return { ..., activeRole, setActiveRole };
```

#### B. `app/index.js`

Reemplazar el if-else secuencial:

```javascript
useEffect(() => {
  if (!cargando) {
    if (!token) {
      router.replace('/(auth)/login');
      return;
    }
    const roles = usuario?.roles ?? [];
    if (roles.length === 0) {
      router.replace('/(auth)/login');
      return;
    }
    // Si tiene más de un rol Y no hay rol activo seleccionado → selector
    if (roles.length > 1 && !activeRole) {
      router.replace('/selector-rol');
      return;
    }
    // Redirigir según el rol activo (o único rol)
    const rolEfectivo = activeRole ?? roles[0];
    if (rolEfectivo === ROLES.CONDUCTOR) {
      router.replace('/(conductor)');
    } else if (rolEfectivo === ROLES.AGENTE) {
      router.replace('/(agente)');
    } else {
      router.replace('/(auth)/login');
    }
  }
}, [cargando, token, activeRole, usuario]);
```

#### C. `app/selector-rol.js` (nuevo archivo)

```javascript
// Pantalla simple que muestra los roles disponibles y permite elegir
// Al seleccionar: llama setActiveRole(rol) → AuthContext persiste en SecureStore
// Luego navega al layout correspondiente
```

Contenido mínimo de la pantalla:
- Título: "¿Cómo deseas ingresar hoy?"
- Botón "Ingresar como Conductor" (si tiene rol conductor)
- Botón "Ingresar como Agente de Parqueo" (si tiene rol agente_parqueo)
- Solo se muestra si el usuario tiene 2+ roles

#### D. Pantallas de perfil — agregar botón "Cambiar modo"

**`app/(conductor)/(tabs)/perfil.js`** — agregar sección si `usuario.roles.length > 1`:

```javascript
{hasAnyRole([ROLES.AGENTE]) && (
  <BotonSecundario
    titulo="Cambiar a modo Agente"
    onPress={async () => {
      await setActiveRole(ROLES.AGENTE);
      router.replace('/(agente)');
    }}
  />
)}
```

**`app/(agente)/(tabs)/perfil.js`** — agregar sección si tiene rol conductor:

```javascript
{hasAnyRole([ROLES.CONDUCTOR]) && (
  <BotonSecundario
    titulo="Cambiar a modo Conductor"
    onPress={async () => {
      await setActiveRole(ROLES.CONDUCTOR);
      router.replace('/(conductor)');
    }}
  />
)}
```

#### E. Guards en layouts (opcional pero recomendado)

**`app/(conductor)/_layout.js`** — verificar que `activeRole === 'conductor'`:

```javascript
// Si el rol activo no es conductor, redirigir al selector
useEffect(() => {
  if (!cargando && token && activeRole && activeRole !== ROLES.CONDUCTOR) {
    router.replace('/selector-rol');
  }
}, [activeRole, cargando, token]);
```

---

## 8. Cambios requeridos en backend/API

El backend **ya está correcto**. `MovilAuthController@login` y `@me` retornan `roles[]`. No se requieren cambios en el backend para este fix.

**Verificar que:**
- `GET /api/v1/movil/me` retorna `roles[]` con todos los roles del usuario
- El campo `rol` singular no se usa como fuente de verdad en ningún endpoint de autorización

---

## 9. Archivos exactos afectados

| Archivo | Cambio | Tipo |
|---------|--------|------|
| `src/context/AuthContext.js` | Agregar `activeRole`, `setActiveRole`, persistencia en SecureStore | Modificar |
| `app/index.js` | Reemplazar if-else por lógica con `activeRole` | Modificar |
| `app/selector-rol.js` | Nueva pantalla de selección de rol | Crear |
| `app/_layout.js` | Agregar `/selector-rol` al stack del RootLayout | Modificar |
| `app/(conductor)/(tabs)/perfil.js` | Agregar botón "Cambiar a modo Agente" condicional | Modificar |
| `app/(agente)/(tabs)/perfil.js` | Agregar botón "Cambiar a modo Conductor" condicional | Modificar |
| `app/(conductor)/_layout.js` | Agregar guard que verifica `activeRole === 'conductor'` | Modificar (opcional) |
| `app/(agente)/_layout.js` | Agregar guard que verifica `activeRole === 'agente_parqueo'` | Modificar (opcional) |

---

## 10. Criterios de aceptación

| Criterio | Verificación |
|----------|-------------|
| Usuario con rol conductor (solo): redirige a `/(conductor)` directamente | Login con `conductor@simetsa.gob.ec` |
| Usuario con rol agente (solo): redirige a `/(agente)` directamente | Login con `agente@simetsa.gob.ec` |
| Usuario con ambos roles: muestra pantalla `selector-rol` | Crear usuario de prueba con roles conductor+agente |
| Seleccionar "Conductor" en selector: redirige a `/(conductor)` | Verificar navegación |
| Seleccionar "Agente" en selector: redirige a `/(agente)` | Verificar navegación |
| Usuario conductor+agente en perfil conductor: ve botón "Cambiar a modo Agente" | Verificar UI |
| Al cambiar a modo agente: redirige a `/(agente)` sin logout | Verificar persistencia de token |
| Al cambiar a modo conductor: redirige a `/(conductor)` sin logout | Verificar persistencia de token |
| La app no toma incorrectamente solo el primer rol del arreglo | Verificar con usuario multi-rol |
| La app no oculta "Comprar ticket" en modo conductor | Verificar pestaña Tickets |
| La app no oculta "Cancelar ticket" en modo conductor | Verificar detalle de ticket |
| El backend valida permisos por acción, no solo por visibilidad | Intentar POST /tickets sin permiso |
| Al cerrar sesión: se borra `activeRole` de SecureStore | Verificar re-login sin selector incorrecto |

---

## 11. Prompt recomendado para Fase 9.5.2

```
Estamos en Fase 9.5.2 del proyecto SIMETSA — Fix multi-rol en app móvil Expo.

La app está en /workspace/simetsa-movil (Expo SDK 56, JS puro, Expo Router).
El backend está en /workspace/simetsa (Laravel 11).

BUG: Un usuario con roles ['conductor', 'agente_parqueo'] siempre es redirigido al 
layout /(conductor) porque app/index.js:21-24 usa un if-else secuencial.

Implementar:
1. Agregar `activeRole` (string) y `setActiveRole(rol)` al AuthContext en 
   src/context/AuthContext.js — persistir en SecureStore con key 'simetsa_active_role'
2. Crear app/selector-rol.js — pantalla de selección que aparece solo si roles.length > 1
3. Modificar app/index.js — si roles.length > 1 y sin activeRole → /selector-rol; 
   si activeRole o 1 rol → redirect directo al layout correspondiente
4. Actualizar app/_layout.js para incluir 'selector-rol' en el stack raíz
5. Actualizar app/(conductor)/(tabs)/perfil.js — botón condicional "Cambiar a modo Agente"
6. Actualizar app/(agente)/(tabs)/perfil.js — botón condicional "Cambiar a modo Conductor"

Leer antes de implementar:
- docs/auditoria-app-movil-multirol.md (diagnóstico completo + propuesta de solución)
- docs/plan-fases-post-9.md (contexto de la fase)
- src/context/AuthContext.js (estado actual)
- app/index.js (bug a corregir)
- app/(conductor)/(tabs)/perfil.js y app/(agente)/(tabs)/perfil.js

Criterios de aceptación: ver docs/auditoria-app-movil-multirol.md sección 10.
```
