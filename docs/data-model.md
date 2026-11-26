# Modelo de datos y reglas de integridad

**Motor principal:** PostgreSQL. Migraciones bajo `backend/database/migrations`; tests pueden usar SQLite en memoria. Este documento no reemplaza el esquema real de las migraciones.

## Tablas de dominio

| Tabla | Contenido | Relaciones y reglas |
|---|---|---|
| `users` | Nombre, email, hash y rol | Email único, roles en Policies y servicio administrativo |
| `monitored_hosts` | Identidad, IP, token, estado de agente, `last_seen_at` | Relaciona servicios, métricas y alertas |
| `monitored_services` | Nombre, host, puerto, protocolo, estado de servicio | Unique `(monitored_host_id,port,protocol)` |
| `host_metrics` | CPU/RAM/disco, uptime, `reading_id`, `recorded_at` | `reading_id` deduplica **por host**; orden cronológico |
| `service_checks` | Resultado TCP, duración, mensaje, `checked_at` | Referencia a un servicio; historial append-only desde panel |
| `alerts` | Tipo, severidad, estado, tiempo de activación/resolución | Puede referenciar host y servicio |

Tablas auxiliares: `jobs`, `failed_jobs`, `cache`, `cache_locks`, `sessions`, `personal_access_tokens` según migraciones de Laravel.

## Reglas importantes

1. **Estado del host y servicios:** `monitored_hosts.status` y `last_seen_at` pertenecen al agente; los servicios TCP tienen estado independiente. El indicador agregado de servicios no sustituye al estado del agente.
2. **Métricas idempotentes:** índice único para `(monitored_host_id, reading_id)`; se permiten lecturas históricas sin UUID por compatibilidad. Comprobar semántica concreta en la migración de `reading_id`.
3. **Última métrica:** `recorded_at DESC, id DESC`, no `MAX(id)` a secas. El índice `(monitored_host_id, recorded_at, id)` facilita las búsquedas.
4. **Retención:** `monitor:cleanup` elimina únicamente métricas y chequeos anteriores al umbral; las alertas no se borran por ese comando.
5. **Control de acceso:** las Policies del panel no impiden todas las escrituras por ORM/SQL ni efectos de relaciones.

## Integridad referencial y eliminación

Las migraciones incluyen `cascadeOnDelete()` para servicios y ciertos históricos. **Eliminar un host o servicio puede eliminar información relacionada sin pasar por `monitor:cleanup`.** Verifica las relaciones antes de aceptar una operación destructiva; no describas el historial como inmutable a nivel de base de datos.

## Migraciones y respaldo

```bash
cd backend
docker compose --env-file .env.docker exec app php artisan migrate:status
```

La migración `fresh` borra todas las tablas. No se debe ejecutar sobre una base importante. Para modificar tipos, índices o constraints, añadir migración reversible y prueba en PostgreSQL.
