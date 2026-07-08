# InfraWatch Backend

Backend principal de InfraWatch desarrollado con **Laravel**, **Filament** y **PostgreSQL**.

Este módulo administra equipos monitoreados, servicios, métricas, alertas, historial de chequeos y la API para agentes externos.

---

## Tecnologías

| Tecnología | Uso |
|---|---|
| Laravel | Backend principal |
| Filament | Panel administrativo |
| PostgreSQL | Base de datos |
| Docker | Base de datos local |
| Composer | Dependencias PHP |
| Telegram | Envio de Notificaciones|

---

## Requisitos

- PHP compatible con la versión del proyecto Laravel.
- Composer.
- Docker.
- PostgreSQL mediante Docker Compose.
- Node.js y NPM si se requiere compilar assets.
- Telegram API

---

## Instalación

### 1. Entrar a la carpeta del backend

```bash
cd backend
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Copiar archivo de entorno

```bash
cp .env.example .env
```

### 4. Generar llave de aplicación

```bash
php artisan key:generate
```

### 5. Levantar PostgreSQL

```bash
docker compose up -d
```

### 6. Ejecutar migraciones

```bash
php artisan migrate
```

### 7. Crear usuario administrador

```bash
php artisan make:filament-user
```

### 8. Levantar servidor local

```bash
php artisan serve
```

Panel administrativo:

```text
http://127.0.0.1:8000/admin
```

---

## Configuración de base de datos

Configuración esperada en `.env` para desarrollo local:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=infrawatch
DB_USERNAME=infrawatch_user
DB_PASSWORD=infrawatch_password
```

---

## Docker Compose

Iniciar PostgreSQL:

```bash
docker compose up -d
```

Detener contenedores:

```bash
docker compose down
```

Revisar contenedores activos:

```bash
docker ps
```

---

## Panel administrativo

Ruta local:

```text
/admin
```

Módulos disponibles:

- Monitored Hosts
- Monitored Services
- Host Metrics
- Service Checks
- Alerts
- Dashboard

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
9. Resuelve alertas abiertas si el servicio vuelve a responder.

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

Ejemplo:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('monitor:services')->everyMinute();
```

---

## API

El backend expone una API para recibir métricas del agente Python.

Endpoint:

```http
POST /api/agent/metrics
```

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

[Ver documentación de Telegram](../docs/telegram.md)

---

## Modelos principales

| Modelo | Descripción |
|---|---|
| `MonitoredHost` | Equipo monitoreado. |
| `MonitoredService` | Servicio TCP asociado a un equipo. |
| `ServiceCheck` | Registro histórico de revisión de un servicio. |
| `HostMetric` | Métrica enviada por un agente. |
| `Alert` | Alerta generada por fallas o eventos importantes. |

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

---

## Seguridad

No subir el archivo `.env`.

El archivo `.env.example` sí puede subirse porque solo contiene valores de ejemplo.

El token real del agente debe mantenerse privado.
