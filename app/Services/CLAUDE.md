# Convenciones de Services

Los servicios (`app/Services/*Service.php`) contienen **toda la lógica de negocio** del proyecto. Se reutilizan idénticamente entre el controller web y el controller API.

---

## Reglas duras

- **Framework-agnósticos**: NO importan `Illuminate\Http\Request`, `redirect()`, `view()`, ni la sesión. Reciben primitivos/arrays y modelos; devuelven primitivos/arrays y modelos.
- Operaciones multi-tabla envueltas en `DB::transaction(function () use (...) { ... })`.
- Errores de negocio → `throw new DomainException('mensaje en español')`. El controller los captura y los convierte en flash error o JSON 422.
- Operaciones críticas (uploads, pagos, integraciones externas) en `try/catch` con `Log::error(...)`.
- Naming **por agregado** (no por entidad). `PuntoVentaService` cubre `activar`, `cambiarEstado`; `AmonestacionService` cubre `registrar`, `actualizar`, `recalcular`.
- Métodos en **verbo, en español**: `activar`, `registrar`, `aprobar`, `recalcular`, `liquidar`.
- PHPDoc en cada método público (incluyendo `@param` con array shape cuando aplique) y citas a la Ordenanza cuando corresponda.

---

## Estructura típica

```php
<?php

namespace App\Services;

use App\Models\PuntoVenta;
use App\Models\SolicitudPuntoVenta;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de activación de puntos de venta (Art. 31, Art. 21).
 */
class PuntoVentaService
{
    /**
     * Firma el contrato y activa el punto de venta.
     *
     * @param  array{email:string, numero_contrato:string, fecha_firma:string, ...}  $datos
     * @return array{punto_venta: PuntoVenta, password_temporal: ?string}
     */
    public function activar(SolicitudPuntoVenta $solicitud, array $datos): array
    {
        if ($solicitud->estado !== SolicitudPuntoVenta::ESTADO_CONTRATO) {
            throw new DomainException('La solicitud no está en etapa de contrato.');
        }
        // ... más guardas de negocio ...

        return DB::transaction(function () use ($solicitud, $datos) {
            // ... operaciones multi-tabla ...
        });
    }
}
```

---

## Patrones consolidados del proyecto

- **Resolución de identidad por cédula**: cuando una operación crea/vincula un usuario a partir de una solicitud, el orden es **cédula → correo → crear**. Ver `PuntoVentaService::activar` como referencia.
  - Si la cédula ya pertenece a un usuario y el correo es distinto/nuevo → `DomainException` explicando a qué cuenta pertenece.
  - Si el correo existe pero su perfil tiene otra cédula → `DomainException`.
  - Si nada existe → crear usuario + perfil con la cédula.

- **Generación de códigos**: helper `generarCodigo()` por servicio (`PV-XXXX`, `AG-XXXX`, `SPV-XXXX`, `SA-XXXX`, `CUR-XXXX`, etc.) usando `withTrashed()->max('id') + 1` con `str_pad`.

- **Recálculo de estado del agregado**: cuando un cambio en un sub-registro afecta el estado del agregado (ej. amonestaciones renumeran y pueden terminar al agente en Art. 40), exponer `registrar()/actualizar()/eliminar()` públicos que llaman a un `private recalcular()` interno. El recalcular ordena, renumera y ajusta el estado del agregado.

- **Reglas relajadas para duplicados**: en sub-registros editables (asignaciones de zona, horarios rotativos), bloquear solo el **duplicado exacto** (misma zona + misma fecha de inicio / misma zona + día + misma "vigente desde"). Permitir distintos periodos/años.

---

## Actions

`app/Actions/NombreAction.php` (con método `execute()`) es la alternativa para operaciones puntuales de un solo paso. Por defecto preferimos **Services** para mantener la cohesión por agregado. Crear una Action solo cuando un servicio crezca demasiado y la operación sea claramente independiente.