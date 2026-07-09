# Docker Compose

InfraWatch incluye una configuración de Docker Compose para levantar el backend, PostgreSQL y procesos auxiliares.

---

## Servicios incluidos

| Servicio | Descripción |
|---|---|
| `app` | Aplicación Laravel ejecutada con `php artisan serve`. |
| `postgres` | Base de datos PostgreSQL. |
| `scheduler` | Ejecuta Laravel Scheduler con `php artisan schedule:work`. |
| `queue` | Worker de colas preparado para uso futuro. Se ejecuta mediante profile opcional. |

---

## Levantar servicios principales

Desde la carpeta `backend`:

```bash
docker compose up -d --build
```

Esto levanta:

- Laravel app
- PostgreSQL
- Scheduler

---

## Ver servicios activos

```bash
docker compose ps
```

---

## Ver logs

Laravel app:

```bash
docker compose logs -f app
```

Scheduler:

```bash
docker compose logs -f scheduler
```

PostgreSQL:

```bash
docker compose logs -f postgres
```

---

## Acceder al panel

```text
http://127.0.0.1:8000/admin
```

---

## Crear usuario administrador

```bash
docker compose exec app php artisan make:filament-user
```

---

## Ejecutar comandos Artisan

Migraciones:

```bash
docker compose exec app php artisan migrate
```

Monitoreo TCP:

```bash
docker compose exec app php artisan monitor:services
```

Limpieza de historial:

```bash
docker compose exec app php artisan monitor:cleanup --days=30 --dry-run
```

Tests:

```bash
docker compose exec app php artisan test
```

---

## Levantar queue worker opcional

El servicio `queue` está preparado con profile opcional.

Para levantarlo:

```bash
docker compose --profile queue up -d
```

Para ver logs:

```bash
docker compose logs -f queue
```

---

## Detener servicios

```bash
docker compose down
```

---

## Detener y eliminar volumen de base de datos

Esto elimina los datos de PostgreSQL.

```bash
docker compose down -v
```

---

## Variables importantes

Dentro de Docker, el backend debe usar:

```env
DB_HOST=postgres
```

Cuando se ejecuta Laravel fuera de Docker, normalmente se usa:

```env
DB_HOST=127.0.0.1
```

---

## Notas

Esta configuración está pensada para desarrollo y demostración técnica.

Para producción se recomienda:

- Usar Nginx o Caddy como servidor web.
- Ejecutar PHP-FPM en lugar de `php artisan serve`.
- Usar variables de entorno seguras.
- Activar HTTPS.
- Separar credenciales reales del repositorio.
