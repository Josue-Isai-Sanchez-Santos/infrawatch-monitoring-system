# Backend InfraWatch

Backend **Laravel 13 / PHP 8.4**, interfaz **Filament 3**, PostgreSQL 16, Laravel Reverb, Scheduler y workers de colas. La aplicación no está migrada a Filament 5.

## Punto de entrada

| Componente | Ubicación |
|---|---|
| API de métricas | `routes/api.php` y `app/Http/Controllers/Api/AgentMetricController.php` |
| Monitor TCP | `app/Console/Commands/CheckMonitoredServices.php` |
| Abstracción TCP | `app/Services/TcpServiceChecker.php` |
| Limpieza de métricas | `app/Console/Commands/CleanupMonitoringHistory.php` |
| Resolución manual de alertas | `app/Services/AlertResolutionService.php` |
| Administración de cuentas | `app/Services/UserAdministrationService.php` |
| Filament | `app/Filament/` |
| Eventos de dashboard | `app/Events/DashboardUpdated.php` |
| Telegram | `app/Jobs/SendTelegramAlert.php`, `app/Services/TelegramNotifier.php` |
| Programación | `routes/console.php` |
| Datos | `database/migrations/`, `app/Models/` |

## Requisitos y configuración

- `composer.json`: PHP `>=8.4.1 <8.6`.
- Imagen PHP: `docker/php/Dockerfile`; PostgreSQL: `docker-compose.yml`.
- `./.env.example`: valores solo de desarrollo **para Laravel ejecutado fuera del contenedor**.
- `./.env.docker.example`: plantilla de interpolación Compose; requiere credenciales locales propias en `.env.docker`.
- Las variables `environment:` de Compose asignan `DB_HOST=postgres` dentro de los contenedores; desde un host usa `127.0.0.1` cuando el puerto está disponible.

Para arrancar consulta [guía completa de instalación](../docs/installation.md) y [Docker](../docs/docker.md). **No sobreescribas `.env` o `.env.docker` existentes** con sus plantillas.

```bash
# Desde backend/
docker compose --env-file .env.docker config --quiet
docker compose --env-file .env.docker up -d --build
docker compose --env-file .env.docker exec app php artisan migrate:status
docker compose --env-file .env.docker exec app php artisan route:list
```

La app en Compose ejecuta `composer install`, migraciones pendientes y `artisan serve`; esta configuración está destinada a desarrollo. Para crear un usuario del panel usa `php artisan make:filament-user` y confirma que su rol sea `admin` antes de depender de esa cuenta como único administrador.

## Operación

```bash
docker compose --env-file .env.docker exec app php artisan monitor:services
docker compose --env-file .env.docker exec app php artisan monitor:cleanup --days=30 --dry-run
docker compose --env-file .env.docker logs --tail=100 queue
docker compose --env-file .env.docker logs --tail=100 broadcast-worker
```

El monitor procesa **TCP** y jamás sustituye el indicador de actividad del agente. Cuando falla un servicio abre una alerta; cuando se recupera resuelve las alertas abiertas correspondientes. Telegram es opcional. El canal Reverb publica `dashboard.updated` y utiliza la cola `broadcasts`.

## Roles

| Acceso | Admin | Técnico | Observador |
|---|---|---|---|
| Ver infraestructura, métricas, chequeos y alertas | Sí | Sí | Sí |
| Crear/modificar hosts y servicios | Sí | Sí | No |
| Resolver alertas | Sí | Sí | No |
| Administrar usuarios | Sí | No | No |
| Crear/editar/eliminar manualmente historial | No | No | No |

Se protege la última cuenta administradora y no existe eliminación masiva de usuarios. Ver [security-model](../docs/security-model.md).

## Pruebas

```bash
docker compose --env-file .env.docker exec app ./vendor/bin/pint --test
docker compose --env-file .env.docker exec app composer audit --locked --no-interaction
docker compose --env-file .env.docker exec \
  -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: \
  -e DB_URL= -e QUEUE_CONNECTION=sync -e BROADCAST_CONNECTION=null \
  app php artisan test
```

El workflow `Backend CI` usa **PostgreSQL** y comprueba además dependencias de Node, Vite y la configuración de Docker. No ejecutar pruebas de integración contra la BD operativa. Guía: [testing-ci](../docs/testing-ci.md).

## Documentación

- [Índice completo](../docs/index.md)
- [Arquitectura](../docs/architecture.md)
- [Modelo de datos](../docs/data-model.md)
- [API](../docs/api.md)
- [Telegram](../docs/telegram.md)
- [WebSocket](../docs/realtime-websockets.md)
- [Seguridad y reportes privados](../SECURITY.md)
