# Arquitectura de InfraWatch

InfraWatch es un sistema de monitoreo de infraestructura TI compuesto por un backend Laravel, un panel administrativo con Filament, una base de datos PostgreSQL, comandos programados de monitoreo, un agente Python para métricas y notificaciones externas vía Telegram.

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

---

## Componentes principales

---

### 1. Backend Laravel

El backend es el núcleo del sistema.

Responsabilidades:

- Administrar equipos monitoreados.
- Administrar servicios monitoreados.
- Recibir métricas del agente.
- Ejecutar comandos de monitoreo TCP.
- Guardar historial de chequeos.
- Guardar métricas del sistema.
- Generar alertas.
- Resolver alertas automáticamente.
- Enviar notificaciones vía Telegram.
- Mostrar información en el panel administrativo.

Tecnologías:

- Laravel
- PHP
- Filament
- PostgreSQL
- Telegram Bot API

---

### 2. Panel administrativo Filament

Filament funciona como interfaz administrativa.

Módulos visibles:

- Dashboard
- Monitoring Control
- Monitored Hosts
- Monitored Services
- Service Checks
- Host Metrics
- Alerts

Desde el panel se pueden registrar equipos, registrar servicios, revisar métricas, consultar alertas, visualizar gráficas y ejecutar procesos de monitoreo.

---

### 3. Dashboard

El dashboard muestra el estado general de la infraestructura.

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

La página `Monitoring Control` permite ejecutar procesos desde el panel administrativo.

Acciones disponibles:

1. Iniciar lectura TCP automática.
2. Ejecutar lectura TCP manual de una sola vez.
3. Iniciar agente automático.
4. Ejecutar agente manualmente una sola vez.

Esta funcionalidad está pensada para desarrollo local y demostraciones.

En producción se recomienda manejar procesos automáticos con:

- cron
- systemd
- supervisor
- Docker services

---

### 5. Base de datos PostgreSQL

PostgreSQL almacena la información principal del sistema.

Tablas principales:

- `users`
- `monitored_hosts`
- `monitored_services`
- `service_checks`
- `host_metrics`
- `alerts`

---

### 6. Comando de monitoreo de servicios

El comando Artisan:

```bash
php artisan monitor:services
```

revisa los servicios registrados mediante conexión TCP.

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

---

### 7. Scheduler de Laravel

El scheduler permite ejecutar el comando de monitoreo automáticamente.

Configuración típica:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('monitor:services')->everyMinute();
```

En desarrollo se ejecuta con:

```bash
php artisan schedule:work
```

---

### 8. Agente Python

El agente Python se ejecuta en el equipo monitoreado.

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

El agente puede ejecutarse una sola vez:

```bash
python agent.py --once
```

O de forma continua:

```bash
python agent.py --interval 60
```

---

### 9. API REST

El backend expone una API para recibir métricas desde agentes.

Endpoint principal:

```http
POST /api/agent/metrics
```

La API valida el token enviado por el agente contra el campo:

```text
monitored_hosts.agent_token
```

Si el token es válido, guarda las métricas en:

```text
host_metrics
```

---

### 10. Notificaciones por Telegram

InfraWatch puede enviar mensajes a Telegram cuando:

- Un servicio registrado deja de responder.
- Se crea una alerta nueva.
- Un servicio vuelve a estar disponible.
- Una alerta abierta se resuelve automáticamente.

Archivo principal:

```text
app/Services/TelegramNotifier.php
```

Configuración:

```text
config/services.php
```

Variables de entorno:

```env
TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=your-telegram-bot-token
TELEGRAM_CHAT_ID=your-chat-id
```

---

## Flujo de monitoreo de servicios

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
Dashboard muestra estado actualizado
```

---

## Flujo de métricas del agente

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
| Telegram                |
| Alertas y recuperación  |
+-------------------------+
```

---

# Modelo de datos resumido

---

## `monitored_hosts`

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
- `created_at`
- `updated_at`

Relaciones:

- Tiene muchos servicios.
- Tiene muchas métricas.
- Tiene muchas alertas.

---

## `monitored_services`

Guarda servicios asociados a un equipo.

Campos principales:

- `id`
- `monitored_host_id`
- `name`
- `port`
- `protocol`
- `status`
- `last_checked_at`
- `created_at`
- `updated_at`

Relaciones:

- Pertenece a un equipo.
- Tiene muchos chequeos.
- Tiene muchas alertas.

---

## `service_checks`

Guarda historial de revisiones de servicios.

Campos principales:

- `id`
- `monitored_service_id`
- `status`
- `response_time_ms`
- `message`
- `checked_at`
- `created_at`
- `updated_at`

Relaciones:

- Pertenece a un servicio monitoreado.

---

## `host_metrics`

Guarda métricas enviadas por agentes.

Campos principales:

- `id`
- `monitored_host_id`
- `cpu_usage`
- `ram_usage`
- `disk_usage`
- `uptime_seconds`
- `recorded_at`
- `created_at`
- `updated_at`

Relaciones:

- Pertenece a un equipo monitoreado.

---

## `alerts`

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
- `created_at`
- `updated_at`

Relaciones:

- Puede pertenecer a un equipo.
- Puede pertenecer a un servicio.

---

## Decisiones técnicas

### Uso de Laravel

Laravel permite construir una API y lógica de backend de forma ordenada usando modelos, migraciones, comandos Artisan, scheduler y servicios internos.

### Uso de Filament

Filament acelera la creación del panel administrativo, recursos CRUD, widgets, gráficas y páginas internas.

### Uso de PostgreSQL

PostgreSQL permite manejar datos relacionales, integridad referencial e historial de monitoreo de forma robusta.

### Uso de Python

Python permite obtener métricas del sistema de forma sencilla mediante librerías como `psutil`.

### Uso de Telegram

Telegram permite enviar notificaciones externas de forma rápida y visible, útil para demostrar alertas automáticas en un sistema de monitoreo.

---

## Estado actual de la arquitectura

Actualmente InfraWatch cuenta con:

- Backend Laravel funcional.
- PostgreSQL en Docker.
- Panel administrativo con Filament.
- Monitoreo TCP.
- Historial de chequeos.
- Alertas básicas.
- Resolución automática de alertas.
- Notificaciones por Telegram.
- Agente Python.
- Configuración segura del agente mediante `.env`.
- API de recepción de métricas.
- Dashboard administrativo.
- Gráficas de CPU, RAM y disco.
- Panel de control para ejecución manual y automática.

---

## Posibles mejoras arquitectónicas

- Separar frontend público y backend API.
- Agregar colas para procesamiento asíncrono.
- Usar Redis para tareas programadas o caché.
- Integrar WebSockets para actualizaciones en tiempo real.
- Crear Docker Compose completo para todo el sistema.
- Implementar autenticación avanzada para agentes.
- Agregar roles y permisos.
- Agregar sistema multiempresa o multisede.
- Instalar el agente como servicio del sistema.
