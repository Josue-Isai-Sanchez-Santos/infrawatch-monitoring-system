# Arquitectura de InfraWatch

InfraWatch es un sistema de monitoreo de infraestructura TI compuesto por un backend Laravel, un panel administrativo con Filament, una base de datos PostgreSQL, comandos programados de monitoreo, un agente Python, notificaciones externas vía Telegram y actualización en tiempo real mediante WebSocket.

---

## Estado de versión

**InfraWatch V2.0 cerrada**

La versión 2.0 integra monitoreo TCP, agente Python, dashboard avanzado, roles, permisos, Telegram, limpieza automática, tests, CI, Docker Compose y WebSocket con Laravel Reverb.

---

## Objetivo de la arquitectura

La arquitectura busca permitir:

- Registro centralizado de equipos.
- Monitoreo de servicios TCP.
- Recolección de métricas de sistema.
- Historial de revisiones.
- Generación y resolución de alertas.
- Visualización desde un dashboard administrativo.
- Ejecución manual y automática de procesos de monitoreo.
- Envío de notificaciones por Telegram cuando un servicio falla o se recupera.
- Actualización del panel administrativo en tiempo real.
- Mantenimiento automático de historial antiguo.
- Separación básica de permisos por rol.

---

## Componentes principales

### 1. Backend Laravel

Responsabilidades:

- Administrar equipos monitoreados.
- Administrar servicios monitoreados.
- Recibir métricas del agente.
- Ejecutar comandos de monitoreo TCP.
- Ejecutar limpieza automática de historial.
- Guardar historial de chequeos.
- Guardar métricas del sistema.
- Generar alertas.
- Resolver alertas automáticamente.
- Enviar notificaciones vía Telegram.
- Disparar eventos realtime con Laravel Reverb.
- Mostrar información en el panel administrativo.

Tecnologías:

- Laravel
- PHP
- Filament
- PostgreSQL
- Telegram Bot API
- Laravel Reverb

---

### 2. Panel administrativo Filament

Módulos visibles:

- Dashboard
- Monitoring Control
- Monitored Hosts
- Monitored Services
- Service Checks
- Host Metrics
- Alerts
- Users

Desde el panel se pueden registrar equipos, registrar servicios, revisar métricas, consultar alertas, visualizar gráficas, administrar usuarios y ejecutar procesos de monitoreo.

---

### 3. Dashboard

Widgets principales:

- Salud general.
- Equipos monitoreados.
- Servicios monitoreados.
- Alertas abiertas.
- Gráfica de CPU.
- Gráfica de RAM.
- Gráfica de disco.
- Hosts con mayor uso de recursos.
- Servicios caídos.
- Últimas alertas abiertas.
- Últimos chequeos TCP.
- Últimas métricas recibidas.

---

### 4. Monitoring Control

Acciones disponibles:

1. Iniciar lectura TCP automática.
2. Detener lectura TCP automática.
3. Ejecutar lectura TCP manual de una sola vez.
4. Iniciar agente automático.
5. Detener agente automático.
6. Ejecutar agente manualmente una sola vez.

Esta funcionalidad está pensada para desarrollo local y demostraciones.

---

### 5. Roles y permisos

Roles internos:

- `admin`
- `technician`
- `observer`

Permisos generales:

| Rol | Permisos |
|---|---|
| Administrador | Acceso total al sistema. |
| Técnico | Revisión de infraestructura, edición operativa y resolución de alertas. |
| Observador | Solo lectura. |

Los permisos se controlan mediante Policies de Laravel y validaciones en recursos de Filament.

---

### 6. Base de datos PostgreSQL

Tablas principales:

- `users`
- `monitored_hosts`
- `monitored_services`
- `service_checks`
- `host_metrics`
- `alerts`

---

### 7. Comando de monitoreo de servicios

```bash
php artisan monitor:services
```

Responsabilidades:

1. Obtener servicios registrados.
2. Leer la IP del equipo asociado.
3. Intentar conexión al puerto configurado.
4. Calcular tiempo de respuesta.
5. Guardar resultado en `service_checks`.
6. Actualizar estado del servicio.
7. Actualizar estado del equipo.
8. Crear alerta si el servicio está caído.
9. Enviar notificación por Telegram si se crea una alerta nueva.
10. Resolver alerta si el servicio vuelve a estar disponible.
11. Enviar notificación de recuperación por Telegram.
12. Disparar evento realtime para actualizar el dashboard.

---

### 8. Limpieza automática de historial

```bash
php artisan monitor:cleanup --days=30 --force
```

El comando elimina historial antiguo de:

- `host_metrics`
- `service_checks`

Conserva:

- `alerts`

El objetivo es evitar crecimiento innecesario de la base de datos.

---

### 9. Scheduler de Laravel

Configuración principal:

```php
Schedule::command('monitor:services')->everyMinute();

Schedule::command('monitor:cleanup --days=30 --force')
    ->dailyAt('03:00');
```

En desarrollo:

```bash
php artisan schedule:work
```

---

### 10. Agente Python

Responsabilidades:

- Leer configuración desde `agent/.env`.
- Obtener hostname.
- Obtener IP.
- Obtener sistema operativo.
- Medir uso de CPU.
- Medir uso de RAM.
- Medir uso de disco.
- Calcular uptime.
- Enviar métricas a la API del backend.
- Generar logs locales.
- Reintentar envíos fallidos.

Ejecución única:

```bash
python agent.py --once
```

Ejecución continua:

```bash
python agent.py --interval 60
```

Modo verbose:

```bash
python agent.py --once --verbose
```

Modo silent:

```bash
python agent.py --interval 60 --silent
```

---

### 11. API REST

Endpoint principal:

```http
POST /api/agent/metrics
```

La API valida el token enviado por el agente contra:

```text
monitored_hosts.agent_token
```

Si el token es válido, guarda las métricas en:

```text
host_metrics
```

Después dispara:

```text
App\Events\DashboardUpdated
```

---

### 12. Notificaciones por Telegram

InfraWatch puede enviar mensajes a Telegram cuando:

- Un servicio registrado deja de responder.
- Se crea una alerta nueva.
- Un servicio vuelve a estar disponible.
- Una alerta abierta se resuelve automáticamente.

Archivo principal:

```text
app/Services/TelegramNotifier.php
```

---

### 13. WebSocket con Laravel Reverb

InfraWatch usa Laravel Reverb para enviar eventos en tiempo real al panel.

Evento principal:

```text
App\Events\DashboardUpdated
```

Canal:

```text
infrawatch.dashboard
```

Evento:

```text
dashboard.updated
```

El frontend escucha el evento con Laravel Echo y recarga el panel administrativo.

---

### 14. Docker Compose

Servicios principales:

- `app`
- `postgres`
- `scheduler`
- `reverb`

Servicio opcional:

- `queue`

Levantar entorno:

```bash
cd backend
docker compose up -d --build
```

---

### 15. Tests

Tests principales:

- API de métricas.
- Creación de hosts.
- Creación de service checks.
- Creación de alerta cuando falla un servicio TCP.

Ejecutar:

```bash
php artisan test
```

---

### 16. GitHub Actions

Archivo:

```text
.github/workflows/backend-ci.yml
```

El workflow ejecuta:

- Composer install.
- Configuración de PHP.
- PostgreSQL para pruebas.
- Migraciones.
- Tests.
- `php artisan route:list`.

---

## Flujos principales

### Flujo de monitoreo TCP

```text
Administrador registra equipo
    ↓
Administrador registra servicio y puerto
    ↓
Laravel Scheduler ejecuta monitor:services
    ↓
El comando intenta conexión TCP
    ↓
Se guarda resultado en service_checks
    ↓
Se actualiza estado del servicio
    ↓
Se genera o resuelve alerta
    ↓
Si aplica, se envía notificación a Telegram
    ↓
Se dispara evento DashboardUpdated
    ↓
Dashboard se actualiza en tiempo real
```

### Flujo de métricas del agente

```text
Equipo monitoreado
    ↓
Agente Python lee configuración desde .env
    ↓
Agente recolecta CPU, RAM, disco y uptime
    ↓
Agente envía HTTP POST a /api/agent/metrics
    ↓
Laravel valida token del agente
    ↓
Laravel actualiza monitored_hosts
    ↓
Laravel guarda registro en host_metrics
    ↓
Laravel dispara DashboardUpdated
    ↓
Filament muestra métricas en Host Metrics y Dashboard
```

---

## Diagrama general

```text
+-------------------------+
| Equipo monitoreado      |
| Agente Python           |
| CPU / RAM / Disco       |
+-----------+-------------+
            |
            | HTTP POST /api/agent/metrics
            v
+-------------------------+
| Backend Laravel         |
| API + Comandos          |
| Filament Admin Panel    |
+-----------+-------------+
            |
            v
+-------------------------+
| PostgreSQL              |
| Hosts / Services        |
| Metrics / Alerts        |
+-----------+-------------+
            |
            v
+-------------------------+
| Dashboard Filament      |
| Gráficas / Alertas      |
| Historial / Métricas    |
+-----------+-------------+
            |
            v
+-------------------------+
| Laravel Reverb          |
| WebSocket realtime      |
+-----------+-------------+
            |
            v
+-------------------------+
| Telegram                |
| Alertas y recuperación  |
+-------------------------+
```

---

## Modelo de datos resumido

### `users`

Guarda usuarios del panel administrativo.

Campos principales:

- `id`
- `name`
- `email`
- `password`
- `role`

### `monitored_hosts`

Guarda equipos monitoreados.

Campos principales:

- `id`
- `name`
- `hostname`
- `ip_address`
- `operating_system`
- `host_type`
- `location`
- `status`
- `agent_token`
- `last_seen_at`

### `monitored_services`

Guarda servicios asociados a un equipo.

Campos principales:

- `id`
- `monitored_host_id`
- `name`
- `port`
- `protocol`
- `status`
- `last_checked_at`

### `service_checks`

Guarda historial de revisiones de servicios.

Campos principales:

- `id`
- `monitored_service_id`
- `status`
- `response_time_ms`
- `message`
- `checked_at`

### `host_metrics`

Guarda métricas enviadas por agentes.

Campos principales:

- `id`
- `monitored_host_id`
- `cpu_usage`
- `ram_usage`
- `disk_usage`
- `uptime_seconds`
- `recorded_at`

### `alerts`

Guarda alertas generadas por el sistema.

Campos principales:

- `id`
- `monitored_host_id`
- `monitored_service_id`
- `type`
- `severity`
- `title`
- `message`
- `status`
- `triggered_at`
- `resolved_at`

---

## Decisiones técnicas

### Uso de Laravel

Laravel permite construir una API y lógica de backend de forma ordenada usando modelos, migraciones, comandos Artisan, scheduler, broadcasting y servicios internos.

### Uso de Filament

Filament acelera la creación del panel administrativo, recursos CRUD, widgets, gráficas y páginas internas.

### Uso de PostgreSQL

PostgreSQL permite manejar datos relacionales, integridad referencial e historial de monitoreo de forma robusta.

### Uso de Python

Python permite obtener métricas del sistema de forma sencilla mediante librerías como `psutil`.

### Uso de Telegram

Telegram permite enviar notificaciones externas de forma rápida y visible, útil para demostrar alertas automáticas en un sistema de monitoreo.

### Uso de Laravel Reverb

Laravel Reverb permite agregar WebSocket al proyecto y actualizar el panel administrativo en tiempo real.

---

## Estado actual de la arquitectura

Actualmente InfraWatch V2.0 cuenta con:

- Backend Laravel funcional.
- PostgreSQL en Docker.
- Docker Compose completo.
- Panel administrativo con Filament.
- Monitoreo TCP.
- Historial de chequeos.
- Alertas básicas.
- Resolución automática de alertas.
- Notificaciones por Telegram.
- Agente Python mejorado.
- Configuración segura del agente mediante `.env`.
- Logs locales del agente.
- Reintentos automáticos del agente.
- API de recepción de métricas.
- Dashboard administrativo.
- Gráficas de CPU, RAM y disco.
- Panel de control para ejecución manual y automática.
- Botones para iniciar y detener procesos.
- Roles y permisos.
- Limpieza automática de historial antiguo.
- Tests básicos.
- GitHub Actions.
- WebSocket con Laravel Reverb.
- Actualización realtime del dashboard.

---

## Posibles mejoras arquitectónicas

- Separar frontend público y backend API.
- Agregar colas reales para procesamiento asíncrono.
- Usar Redis para tareas programadas, colas o caché.
- Actualizar widgets específicos sin recargar toda la página.
- Implementar autenticación avanzada para agentes.
- Agregar sistema multiempresa o multisede.
- Agregar reportes PDF.
- Preparar despliegue productivo con Nginx/Caddy y PHP-FPM.
