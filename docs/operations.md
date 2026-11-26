# Operación y mantenimiento

Esta guía describe comandos de **desarrollo y laboratorio**; consulta [seguridad](../SECURITY.md) antes de acceder desde redes externas.

## Estado, logs y reinicios

```bash
cd backend
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs --tail=100 app
docker compose --env-file .env.docker logs --tail=100 scheduler
docker compose --env-file .env.docker logs --tail=100 queue
docker compose --env-file .env.docker logs --tail=100 broadcast-worker
docker compose --env-file .env.docker logs --tail=100 reverb
```

## Monitoreo TCP y estado de hosts

```bash
docker compose --env-file .env.docker exec app php artisan monitor:services
```

El monitor comprueba conexiones TCP según los servicios registrados, crea un `service_check` por revisión, abre/resuelve alertas y emite un evento de dashboard. No modifica estado ni última comunicación del agente. El scheduler ejecuta la supervisión cada minuto con un mutex en BD.

## Retención del historial

Por defecto `MONITORING_HISTORY_RETENTION_DAYS=30` para `host_metrics` y `service_checks`; **las alertas se conservan**. La ejecución diaria está programada a las 03:00 según la zona horaria del scheduler.

```bash
# Simulación segura
docker compose --env-file .env.docker exec app php artisan monitor:cleanup --days=30 --dry-run
# Solo después de revisar respaldo/impacto:
# docker compose --env-file .env.docker exec app php artisan monitor:cleanup --days=30 --force
```

Una limpieza no equivale a una política absoluta de inmutabilidad: las eliminaciones de hosts/servicios y sus cascadas requieren revisión separada.

## Seguridad de usuarios

Nunca te degrades como último administrador. No existe eliminación masiva desde el panel; un servicio de dominio protege frente a eliminación del último administrador y operaciones de rol peligrosas. Las actualizaciones de cuentas que se hagan directamente en SQL saltan estas salvaguardas.

## Copias de seguridad

Almacena respaldos **fuera del repositorio** y con controles de acceso. Desde `backend`, por ejemplo:

```bash
mkdir -p "$HOME/infrawatch-backups"
docker compose --env-file .env.docker exec -T postgres \
  sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom' \
  > "$HOME/infrawatch-backups/infrawatch-local.dump"
```

Verifica el tamaño/validez del respaldo y prueba **una restauración a una base separada** antes de depender de él. No sustituyas una base productiva directamente. Si una actualización necesita migraciones, realiza primero un respaldo válido y un ensayo en staging.

## Actualizaciones y auditoría

```bash
docker compose --env-file .env.docker exec app composer audit --locked --no-interaction
docker compose --env-file .env.docker exec vite npm audit --audit-level=high
```

Actualiza solo dependencias compatibles y prueba los resultados; Dependabot propone PRs, no sustituye la evaluación humana. No ignores avisos de seguridad para conseguir que CI pase.

## Despliegue público: no listo con Compose de desarrollo

Necesita, como mínimo, PHP-FPM y proxy inverso con HTTPS, WebSocket seguro, puerto PostgreSQL no expuesto, `APP_DEBUG=false`, políticas de secretos y recuperación, monitorización del propio servicio y backups automatizados. No afirmes que un puerto local accesible significa que existe una configuración segura de producción.
