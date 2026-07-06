# Arquitectura de InfraWatch

InfraWatch es un sistema de monitoreo de infraestructura TI compuesto por un backend web, una base de datos PostgreSQL, comandos programados de monitoreo y un agente Python para recolección de métricas.

---

## Objetivo de la arquitectura

La arquitectura busca permitir:

- Registro centralizado de equipos.
- Monitoreo de servicios TCP.
- Recolección de métricas de sistema.
- Historial de revisiones.
- Generación y resolución de alertas.
- Visualización desde un dashboard administrativo.

---

## Componentes principales

### 1. Backend Laravel

El backend es el núcleo del sistema.

Responsabilidades:

- Administrar usuarios.
- Administrar equipos monitoreados.
- Administrar servicios monitoreados.
- Recibir métricas del agente.
- Ejecutar comandos de monitoreo.
- Guardar historial de eventos.
- Generar alertas.
- Mostrar información en el panel administrativo.

Tecnologías:

- Laravel
- PHP
- Filament
- PostgreSQL

---

### 2. Panel Filament

Filament funciona como interfaz administrativa.

Módulos visibles:

- Monitored Hosts
- Monitored Services
- Service Checks
- Host Metrics
- Alerts
- Dashboard

Desde el panel se pueden registrar equipos, servicios, revisar métricas, consultar alertas y visualizar el estado general del sistema.

---

### 3. Base de datos PostgreSQL

PostgreSQL almacena la información principal del sistema.

Tablas principales:

- `monitored_hosts`
- `monitored_services`
- `service_checks`
- `host_metrics`
- `alerts`
- `users`

---

### 4. Comando de monitoreo de servicios

El comando Artisan:

```bash
php artisan monitor:services

revisa los servicios registrados mediante conexión TCP.

Responsabilidades:

Obtener servicios registrados.
Leer la IP del equipo asociado.
Intentar conexión al puerto configurado.
Calcular tiempo de respuesta.
Guardar resultado en service_checks.
Actualizar estado del servicio.
Actualizar estado del equipo.
Crear alerta si el servicio está caído.
Resolver alerta si el servicio vuelve a estar disponible.
5. Scheduler de Laravel

El scheduler permite ejecutar el comando de monitoreo automáticamente.

Configuración típica:

use Illuminate\Support\Facades\Schedule;

Schedule::command('monitor:services')->everyMinute();

En desarrollo se ejecuta con:

php artisan schedule:work
6. Agente Python

El agente Python se ejecuta en el equipo monitoreado.

Responsabilidades:

Obtener hostname.
Obtener IP.
Obtener sistema operativo.
Medir uso de CPU.
Medir uso de RAM.
Medir uso de disco.
Calcular uptime.
Enviar métricas a la API del backend.
Flujo de monitoreo de servicios
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
Dashboard muestra estado actualizado
Flujo de métricas del agente
Equipo monitoreado
    ↓
Agente Python recolecta métricas
    ↓
Agente envía HTTP POST a /api/agent/metrics
    ↓
Laravel valida token del agente
    ↓
Laravel actualiza monitored_hosts
    ↓
Laravel guarda registro en host_metrics
    ↓
Filament muestra las métricas
Diagrama general
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
| Estado general          |
| Alertas e historial     |
+-------------------------+
Modelo de datos resumido
monitored_hosts

Guarda equipos monitoreados.

Campos principales:

id
name
hostname
ip_address
operating_system
host_type
location
status
agent_token
last_seen_at
monitored_services

Guarda servicios asociados a un equipo.

Campos principales:

id
monitored_host_id
name
port
protocol
status
last_checked_at
service_checks

Guarda historial de revisiones de servicios.

Campos principales:

id
monitored_service_id
status
response_time_ms
message
checked_at
host_metrics

Guarda métricas enviadas por agentes.

Campos principales:

id
monitored_host_id
cpu_usage
ram_usage
disk_usage
uptime_seconds
recorded_at
alerts

Guarda alertas generadas por el sistema.

Campos principales:

id
monitored_host_id
monitored_service_id
type
severity
title
message
status
triggered_at
resolved_at
Decisiones técnicas
Uso de Laravel

Laravel permite construir una API y lógica de backend de forma ordenada, usando modelos, migraciones, comandos Artisan y scheduler.

Uso de Filament

Filament acelera la creación del panel administrativo sin tener que construir una interfaz desde cero.

Uso de PostgreSQL

PostgreSQL permite manejar datos relacionales, integridad referencial e historial de monitoreo de forma robusta.

Uso de agente Python

Python permite obtener métricas del sistema de forma sencilla mediante librerías como psutil.

Estado actual de la arquitectura

Actualmente InfraWatch cuenta con:

Backend Laravel funcional.
PostgreSQL en Docker.
Panel administrativo.
Monitoreo TCP.
Historial de chequeos.
Alertas básicas.
Agente Python.
API de recepción de métricas.
Dashboard administrativo.
Posibles mejoras arquitectónicas
Separar frontend público y backend API.
Agregar colas para procesamiento asíncrono.
Usar Redis para tareas programadas o caché.
Integrar WebSockets para actualizaciones en tiempo real.
Crear Docker Compose completo para todo el sistema.
Implementar autenticación avanzada para agentes.
Agregar notificaciones externas.
Agregar sistema multiempresa o multisede.
