# Variables de entorno y configuración

## Archivos y precedencia

| Archivo | Uso | ¿Se incluye en Git? |
|---|---|---|
| `backend/.env.example` | Plantilla Laravel para desarrollo local | Sí |
| `backend/.env` | Configuración efectiva de Laravel fuera de overrides | **No** |
| `backend/.env.docker.example` | Plantilla de valores que interpolan en Compose | Sí |
| `backend/.env.docker` | Contraseñas/identificadores que consume Compose | **No** |
| `agent/.env.example` | Plantilla pública del agente | Sí |
| `agent/.env` | Token y API de un host real | **No** |

Cuando Docker Compose define una variable mediante `environment:`, prevalece sobre valores de `backend/.env` en ese contenedor. Por ejemplo, la conexión a PostgreSQL **desde el contenedor** apunta al servicio `postgres`, no a `127.0.0.1`. Las variables `VITE_REVERB_*` se inyectan en el contenedor de desarrollo `vite` y el navegador utiliza la dirección accesible externamente.

## Backend

| Variable | Uso / ejemplo | Precauciones |
|---|---|---|
| `APP_ENV` | `local` en desarrollo | `production` debe acompañarse de endurecimiento |
| `APP_DEBUG` | `true` solo desarrollo | `false` obligatorio en producción |
| `APP_KEY` | Clave Laravel generada por Artisan | Nunca compartir |
| `DB_CONNECTION` | `pgsql` | No usar DB productiva para tests |
| `DB_HOST` | `127.0.0.1` (host), `postgres` (contenedor) | Diferenciar redes |
| `DB_PORT` | `5432` | Evitar publicación externa |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | DB, usuario y clave | La cuenta debe existir; no reutilizar ejemplos |
| `BROADCAST_CONNECTION` | `reverb` | Una sola definición |
| `QUEUE_CONNECTION` | `database` en Compose | Requiere workers activos |
| `MONITORING_HISTORY_RETENTION_DAYS` | `30` | Afecta métricas y chequeos, no alertas |
| `TELEGRAM_ENABLED` | `false` por defecto | No exponer token de bot |
| `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` | Bot y destino | Guardar fuera de Git |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` | Identificación Reverb | Valores únicos, no ejemplos públicos |
| `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Destino de publicación | En Compose `reverb:8080` |
| `VITE_REVERB_*` | WebSocket desde navegador | Configuración de acceso del cliente |

## Agente

| Variable | Función |
|---|---|
| `API_URL` | URL HTTPS del endpoint `/api/agent/metrics` |
| `AGENT_TOKEN` | Bearer token individual del host |
| `INTERVAL_SECONDS` | Intervalo por defecto al usar `--interval` sin argumento |
| `REQUEST_TIMEOUT_SECONDS` | Límite de tiempo por petición |
| `RETRY_ATTEMPTS`, `RETRY_DELAY_SECONDS` | Política de reintentos transitorios |
| `LOG_FILE`, `LOG_MAX_BYTES`, `LOG_BACKUP_COUNT` | Logs rotativos |

## Comprobaciones sin imprimir secretos

```bash
cd backend
docker compose --env-file .env.docker config --quiet
docker compose --env-file .env.docker exec app php artisan about
```

Ten cuidado con `docker compose config` sin `--quiet`: puede **mostrar variables efectivas y contraseñas en la terminal**. Tampoco publiques salidas completas de `php artisan env`, `printenv`, capturas ni archivos `.env`.

## Entorno de producción

Este repositorio **no ofrece todavía una receta de producción endurecida**. Se requiere proveedor de secretos, HTTPS/WSS, proxy, puertos restringidos, backups probados, `APP_DEBUG=false`, workers supervisados y procedimientos de recuperación. Ver [SECURITY.md](../SECURITY.md) y [operaciones](operations.md).
