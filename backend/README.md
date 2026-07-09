# InfraWatch Backend

Backend principal de InfraWatch desarrollado con **Laravel**, **Filament** y **PostgreSQL**.

Este módulo administra equipos monitoreados, servicios, métricas, alertas, historial de chequeos, dashboard, API para agentes externos, roles, notificaciones vía Telegram y actualización en tiempo real con WebSocket.

---

## Tecnologías

| Tecnología | Uso |
|---|---|
| Laravel | Backend principal |
| Filament | Panel administrativo |
| PostgreSQL | Base de datos |
| Docker Compose | Entorno de desarrollo |
| Composer | Dependencias PHP |
| Telegram Bot API | Envío de notificaciones |
| Laravel Scheduler | Ejecución automática de monitoreo |
| Artisan Commands | Comandos de monitoreo y mantenimiento |
| Laravel Reverb | WebSocket y eventos realtime |
| Laravel Echo | Cliente frontend para WebSocket |
| PHPUnit | Tests automatizados |
| GitHub Actions | Integración continua |

---

## Requisitos

- PHP compatible con la versión del proyecto Laravel.
- Composer.
- Docker.
- Docker Compose.
- PostgreSQL mediante Docker Compose.
- Node.js y NPM para assets y broadcasting.
- Bot de Telegram si se desean notificaciones externas.

---

## Instalación local

### 1. Entrar a la carpeta del backend

```bash
cd backend
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Instalar dependencias Node

```bash
npm install
```

### 4. Copiar archivo de entorno

```bash
cp .env.example .env
```

### 5. Generar llave de aplicación

```bash
php artisan key:generate
```

### 6. Levantar PostgreSQL

```bash
docker compose up -d postgres
```

### 7. Ejecutar migraciones

```bash
php artisan migrate
```

### 8. Crear usuario administrador

```bash
php artisan make:filament-user
```

### 9. Levantar servidor local

```bash
php artisan serve
```

Panel administrativo:

```text
http://127.0.0.1:8000/admin
```

---

## Configuración de base de datos

Configuración esperada en `.env` para desarrollo local fuera de Docker:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=infrawatch
DB_USERNAME=infrawatch_user
DB_PASSWORD=infrawatch_password
```

Dentro de Docker Compose, el host debe ser:

```env
DB_HOST=postgres
```

---

## Docker Compose completo

El backend incluye una configuración de Docker Compose para levantar los servicios principales del sistema.

Servicios incluidos:

- `app`: aplicación Laravel.
- `postgres`: base de datos PostgreSQL.
- `scheduler`: ejecución automática de tareas programadas.
- `reverb`: servidor WebSocket Laravel Reverb.
- `queue`: worker de colas preparado como profile opcional.

Levantar servicios principales:

```bash
docker compose up -d --build
```

Levantar queue worker opcional:

```bash
docker compose --profile queue up -d
```

Documentación completa:

[Ver documentación Docker](../docs/docker.md)

---

## Panel administrativo

Ruta local:

```text
/admin
```

Módulos disponibles:

- Dashboard
- Monitoring Control
- Monitored Hosts
- Monitored Services
- Host Metrics
- Service Checks
- Alerts
- Users

---

## Dashboard

El dashboard de InfraWatch muestra información general del estado de la infraestructura.

Widgets principales:

- Salud general de servicios.
- Equipos monitoreados.
- Servicios monitoreados.
- Alertas abiertas.
- Gráfica de uso de CPU.
- Gráfica de uso de RAM.
- Gráfica de uso de disco.
- Hosts con mayor uso de recursos.
- Servicios caídos.
- Últimas alertas abiertas.
- Últimos chequeos TCP.
- Últimas métricas recibidas.

---

## Panel de control de monitoreo

InfraWatch incluye una página de Filament para ejecutar procesos desde el panel administrativo.

Acciones disponibles:

1. Iniciar lectura TCP automática.
2. Detener lectura TCP automática.
3. Ejecutar lectura TCP manual de una sola vez.
4. Iniciar agente automático.
5. Detener agente automático.
6. Ejecutar agente manualmente una sola vez.

Esta sección está pensada para entorno local o de desarrollo.

En producción se recomienda usar:

- cron
- systemd
- supervisor
- Docker services

---

## Roles y permisos

InfraWatch cuenta con tres roles principales:

| Rol | Permisos |
|---|---|
| Administrador | Acceso total al sistema, usuarios, monitoreo, recursos y eliminación de registros. |
| Técnico | Puede revisar infraestructura, crear/editar hosts y servicios, consultar métricas y resolver alertas. |
| Observador | Solo lectura sobre dashboard, hosts, servicios, métricas, chequeos y alertas. |

La administración de usuarios está disponible solo para administradores.

La página `Monitoring Control` está restringida al rol administrador.

Los permisos se controlan mediante:

```text
app/Policies/
```

---

## Comando de monitoreo de servicios

InfraWatch incluye un comando Artisan para revisar servicios TCP registrados.

```bash
php artisan monitor:services
```

El comando realiza lo siguiente:

1. Lee todos los servicios registrados.
2. Obtiene la IP del equipo asociado.
3. Intenta conectarse al puerto configurado.
4. Mide el tiempo de respuesta.
5. Guarda un registro en `service_checks`.
6. Actualiza el estado del servicio.
7. Actualiza el estado del equipo.
8. Genera una alerta si el servicio no responde.
9. Envía notificación por Telegram si se crea una alerta nueva.
10. Resuelve alertas abiertas si el servicio vuelve a responder.
11. Envía notificación de recuperación por Telegram.
12. Dispara un evento realtime para actualizar el dashboard.

---

## Limpieza automática de historial

InfraWatch incluye un comando para eliminar historial antiguo de monitoreo y evitar crecimiento innecesario de la base de datos.

El comando elimina:

- Métricas antiguas de `host_metrics`.
- Chequeos antiguos de `service_checks`.

El comando conserva:

- Alertas históricas de `alerts`.

Ejecutar limpieza manual:

```bash
php artisan monitor:cleanup --days=30
```

Simular limpieza sin borrar datos:

```bash
php artisan monitor:cleanup --days=30 --dry-run
```

Ejecutar sin confirmación:

```bash
php artisan monitor:cleanup --days=30 --force
```

La limpieza automática se programa desde:

```text
routes/console.php
```

Ejemplo:

```php
Schedule::command('monitor:cleanup --days=30 --force')
    ->dailyAt('03:00');
```

---

## Scheduler

Para ejecutar el monitoreo automáticamente se usa Laravel Scheduler.

En desarrollo:

```bash
php artisan schedule:work
```

Para ejecutar las tareas programadas una sola vez:

```bash
php artisan schedule:run
```

La programación se configura en:

```text
routes/console.php
```

Tareas principales:

```php
Schedule::command('monitor:services')->everyMinute();

Schedule::command('monitor:cleanup --days=30 --force')
    ->dailyAt('03:00');
```

---

## API

El backend expone una API para recibir métricas del agente Python.

Endpoint:

```http
POST /api/agent/metrics
```

Documentación completa:

[Ver documentación de API](../docs/api.md)

---

## Notificaciones por Telegram

El backend puede enviar notificaciones automáticas a Telegram cuando un servicio cae o vuelve a estar disponible.

Variables requeridas en `.env`:

```env
TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=your-telegram-bot-token
TELEGRAM_CHAT_ID=your-chat-id
```

Variables de ejemplo en `.env.example`:

```env
TELEGRAM_ENABLED=false
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
```

Las notificaciones se envían desde el servicio:

```text
app/Services/TelegramNotifier.php
```

El comando `monitor:services` llama este servicio cuando crea o resuelve alertas.

Documentación completa:

[Ver documentación de Telegram](../docs/telegram.md)

---

## WebSocket y actualización en tiempo real

El backend utiliza Laravel Reverb para enviar eventos en tiempo real al panel administrativo.

Evento principal:

```text
App\Events\DashboardUpdated
```

Canal:

```text
infrawatch.dashboard
```

Evento broadcast:

```text
dashboard.updated
```

Ejecutar Reverb en desarrollo:

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080 --debug
```

Ejecutar Vite en desarrollo:

```bash
npm run dev
```

Documentación completa:

[Ver documentación de WebSocket](../docs/realtime-websockets.md)

---

## Tests

El backend incluye tests básicos para validar funcionalidades principales del sistema.

Tests incluidos:

- Envío de métricas por API.
- Rechazo de token faltante o inválido.
- Creación de equipos monitoreados.
- Creación de chequeos de servicio.
- Generación de alerta cuando un servicio TCP falla.

Ejecutar todos los tests:

```bash
php artisan test
```

Ejecutar un test específico:

```bash
php artisan test --filter=AgentMetricsApiTest
```

---

## Integración continua

El backend cuenta con un workflow de GitHub Actions para validar que el proyecto no esté roto después de cada cambio.

Archivo del workflow:

```text
../.github/workflows/backend-ci.yml
```

El proceso ejecuta:

1. Instalación de PHP.
2. Instalación de dependencias Composer.
3. Configuración de entorno.
4. Levantamiento de PostgreSQL para pruebas.
5. Migraciones.
6. Tests automatizados.
7. Revisión de rutas con `php artisan route:list`.

---

## Modelos principales

| Modelo | Descripción |
|---|---|
| `User` | Usuario del panel administrativo con rol asignado. |
| `MonitoredHost` | Equipo monitoreado. |
| `MonitoredService` | Servicio TCP asociado a un equipo. |
| `ServiceCheck` | Registro histórico de revisión de un servicio. |
| `HostMetric` | Métrica enviada por un agente. |
| `Alert` | Alerta generada por fallas o eventos importantes. |

---

## Archivos importantes

| Archivo | Descripción |
|---|---|
| `app/Console/Commands/CheckMonitoredServices.php` | Comando de monitoreo TCP. |
| `app/Console/Commands/CleanupMonitoringHistory.php` | Comando de limpieza de historial. |
| `app/Events/DashboardUpdated.php` | Evento broadcast para actualización realtime. |
| `app/Services/TelegramNotifier.php` | Servicio para notificaciones por Telegram. |
| `app/Filament/Pages/MonitoringControl.php` | Página de control manual y automático. |
| `app/Filament/Resources/UserResource.php` | Administración de usuarios y roles. |
| `app/Filament/Widgets/` | Widgets del dashboard. |
| `app/Policies/` | Control de permisos por rol. |
| `routes/api.php` | Ruta para recepción de métricas del agente. |
| `routes/console.php` | Programación del scheduler. |
| `routes/channels.php` | Canales de broadcasting. |
| `config/services.php` | Configuración de servicios externos. |
| `config/broadcasting.php` | Configuración de broadcasting. |
| `config/reverb.php` | Configuración de Laravel Reverb. |

---

## Comandos útiles

Limpiar caché:

```bash
php artisan optimize:clear
```

Ver rutas:

```bash
php artisan route:list
```

Ver migraciones:

```bash
php artisan migrate:status
```

Recrear base de datos en desarrollo:

```bash
php artisan migrate:fresh
```

Ejecutar monitoreo TCP:

```bash
php artisan monitor:services
```

Ejecutar limpieza de historial:

```bash
php artisan monitor:cleanup --days=30 --dry-run
```

Ejecutar tests:

```bash
php artisan test
```

Formatear código PHP:

```bash
./vendor/bin/pint
```

Compilar assets:

```bash
npm run build
```

---

## Seguridad

No subir el archivo `.env`.

El archivo `.env.example` sí puede subirse porque solo contiene valores de ejemplo.

Credenciales que deben mantenerse privadas:

- `APP_KEY`
- `DB_PASSWORD` si se usa una contraseña real.
- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_CHAT_ID`
- `REVERB_APP_SECRET`
- Tokens de agentes registrados en `monitored_hosts`.

Si alguna credencial se expone, debe regenerarse o cambiarse inmediatamente.
