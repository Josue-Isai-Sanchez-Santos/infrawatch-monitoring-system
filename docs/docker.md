# Docker Compose — InfraWatch (desarrollo)

**Ubicación:** `backend/docker-compose.yml`. Esta guía refleja el Compose de `master`: **todos los servicios están declarados**, no existe profile opcional para `queue`, y las contraseñas se obtienen de `.env.docker`.

## Servicios

| Servicio | Función | Puerto publicado (configuración actual) |
|---|---|---|
| `app` | Laravel bajo `php artisan serve` | 8000 |
| `postgres` | PostgreSQL 16 con volumen `infrawatch_postgres_data` | 5432 |
| `scheduler` | `php artisan schedule:work` | Ninguno |
| `queue` | Trabajos estándar en cola `default` | Ninguno |
| `broadcast-worker` | Trabajos de eventos en cola `broadcasts` | Ninguno |
| `reverb` | WebSocket Reverb | 8080 |
| `vite` | Desarrollo frontend con Node 24 | 127.0.0.1:5173 |

## Preparación

```bash
cd backend
cp .env.example .env
cp .env.docker.example .env.docker
```

**Solo si aún no existen.** Modificar `DB_PASSWORD`, `REVERB_APP_ID`, `REVERB_APP_KEY` y `REVERB_APP_SECRET` en `.env.docker` antes de iniciar. `.env.example` usa valores de demostración: Laravel fuera de Docker espera `127.0.0.1`; Compose define el hostname de servicio `postgres` dentro de los contenedores y otros overrides.

```bash
docker compose --env-file .env.docker config --quiet
docker compose --env-file .env.docker up -d --build
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs --tail=100 app
```

La app ejecuta `composer install` y `php artisan migrate --force` durante su arranque. Evita arrancar instancias contra una base de datos con datos que no hayas respaldado y revisa migraciones antes de desplegar.

## Comandos habituales

```bash
docker compose --env-file .env.docker exec app php artisan key:generate
docker compose --env-file .env.docker exec app php artisan make:filament-user
docker compose --env-file .env.docker exec app php artisan route:list
docker compose --env-file .env.docker exec app php artisan migrate:status
docker compose --env-file .env.docker exec app php artisan monitor:services
docker compose --env-file .env.docker exec app php artisan monitor:cleanup --days=30 --dry-run
docker compose --env-file .env.docker logs --tail=100 queue
docker compose --env-file .env.docker logs --tail=100 broadcast-worker
```

Para usar Laravel **fuera** de Docker, también debes asegurar que la contraseña de `.env` sea igual a la cuenta real de PostgreSQL. Copiar `.env.example` no crea el usuario ni cambia automáticamente la contraseña del contenedor.

## Respaldo de PostgreSQL

Un respaldo consistente puede realizarse con `pg_dump`; antes de restaurar comprueba ruta, permisos, espacio disponible y compatibilidad. Ejemplo de desarrollo (desde `backend`):

```bash
# No imprimir contraseñas en logs ni historial
mkdir -p "$HOME/infrawatch-backups"
# pg_dump corre dentro del contenedor y usa la cuenta de Compose
# El archivo resultante queda en la máquina local
# Ajusta usuario y base si no coinciden con el ejemplo:
docker compose --env-file .env.docker exec -T postgres \
  sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom' \
  > "$HOME/infrawatch-backups/infrawatch-local.dump"
```

**Conserva los respaldos fuera del repositorio y nunca los subas a Git.** Este comando es una referencia operativa; la política de retención y prueba de restauración son responsabilidad del operador. Si hay datos reales, trabaja con una copia antes de ejecutar pruebas destructivas.

## Peligros

- `docker compose down` detiene y elimina contenedores; normalmente conserva volúmenes. **`down -v` elimina volúmenes y puede causar pérdida permanente de datos**.
- `migrate:fresh` **destruye** las tablas existentes.
- Los puertos `8000:8000`, `5432:5432`, `8080:8080` pueden quedar expuestos en interfaces de red. Para laboratorio cerrado puedes vincularlos a `127.0.0.1`; en producción usar proxy inverso, HTTPS/WSS, red privada, secretos únicos y PHP-FPM en lugar del servidor de desarrollo.
- `APP_DEBUG=true` es solo para desarrollo.

Ver [seguridad](../SECURITY.md), [instalación](installation.md), [operación](operations.md) y [diagnóstico](troubleshooting.md).
