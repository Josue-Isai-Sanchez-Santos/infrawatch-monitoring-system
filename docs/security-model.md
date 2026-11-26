# Modelo de autorización y seguridad

## Roles implementados

| Operación | `admin` | `technician` | `observer` |
|---|---|---|---|
| Ver dashboard, hosts, servicios, métricas, chequeos y alertas | Sí | Sí | Sí |
| Crear/editar hosts y servicios | Sí | Sí | No |
| Eliminar hosts y servicios | Sí | No | No |
| Resolver una alerta abierta | Sí | Sí | No |
| Crear alertas manualmente | No | No | No |
| Eliminar alertas | Sí | No | No |
| Gestionar usuarios | Sí | No | No |
| Eliminar usuarios en masa | No | No | No |
| Crear/editar/eliminar métricas y chequeos en Filament | No | No | No |

Las Policies de dominio están en `backend/app/Policies`. Acciones personalizadas, como resolver alertas, también autorizan la operación en `AlertResolutionService`, en lugar de limitarse a ocultar botones.

## Administración de usuarios

`UserAdministrationService` protege operaciones de cambio de rol y eliminación con transacciones y bloqueo de filas administrativas en PostgreSQL. No se permite eliminar la propia cuenta, degradarse a sí mismo, eliminar la última cuenta administradora ni degradar al último administrador. El cast Eloquent `hashed` procesa contraseñas sin aplicar un segundo `Hash::make` en el formulario.

**Límite:** cambios SQL directos o código externo que no usa el servicio pueden saltarse las validaciones. Las pruebas locales con SQLite no reproducen todas las garantías de bloqueo de PostgreSQL. Para entornos con concurrencia hay que comprobarlo contra PostgreSQL real.

## Historial de métricas y chequeos

Los recursos Filament no ofrecen páginas de creación/edición ni autorización para eliminar métricas y chequeos. La retención se ejecuta mediante `monitor:cleanup`. Esto es **solo una regla de panel**, no una política de inmutabilidad transaccional del motor SQL: la eliminación de hosts/servicios puede activar cascadas de claves foráneas.

## Autenticación y límites de la API

- Ruta `POST /api/agent/metrics`, token Bearer asociado a `monitored_hosts.agent_token`.
- Tokens desconocidos/revocados no se admiten; los tokens se ocultan de la serialización del modelo.
- Limitación por token y dirección IP; ante exceso responde HTTP 429.
- Validación de IPv4/IPv6, campos requeridos, rangos 0–100 y UUID opcional.
- Idempotencia de lecturas; un reintento no dispara una segunda creación ni evento.

## Protección operativa

Usar HTTPS/WSS, no exponer PostgreSQL, mantener `APP_DEBUG=false` en producción, segregar cuentas y claves, renovar tokens expuestos y contar con backups. El Compose de desarrollo publica puertos y no debe desplegarse públicamente sin rediseño. Ver [SECURITY.md](../SECURITY.md) y [configuración](configuration.md).
