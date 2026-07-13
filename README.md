# InfraWatch Monitoring System

<p align="center">
  <strong>Sistema web de monitoreo de infraestructura TI</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-Backend-red?style=for-the-badge&logo=laravel" />
  <img src="https://img.shields.io/badge/Filament-Admin%20Panel-orange?style=for-the-badge" />
  <img src="https://img.shields.io/badge/PostgreSQL-Database-blue?style=for-the-badge&logo=postgresql" />
  <img src="https://img.shields.io/badge/Python-Agent-green?style=for-the-badge&logo=python" />
  <img src="https://img.shields.io/badge/Telegram-Alerts-blue?style=for-the-badge&logo=telegram" />
  <img src="https://img.shields.io/badge/Reverb-WebSocket-purple?style=for-the-badge" />
</p>

---

## Estado del proyecto

**Versión actual: V2.0 cerrada**

InfraWatch V2.0 integra monitoreo TCP, agente Python, dashboard avanzado, roles, alertas por Telegram, limpieza automática, tests, CI con GitHub Actions, Docker Compose y actualización en tiempo real mediante WebSocket.

---

## Descripción

**InfraWatch** es un sistema web de monitoreo de infraestructura TI desarrollado con **Laravel**, **Filament**, **PostgreSQL** y un **agente Python**.

El sistema permite registrar equipos, monitorear servicios de red mediante puertos TCP, recolectar métricas del sistema, visualizar gráficas, consultar alertas, recibir notificaciones automáticas vía Telegram y actualizar el panel administrativo en tiempo real mediante WebSocket.

Está pensado como una solución base para supervisar servidores, estaciones de trabajo, servicios internos y métricas básicas como CPU, RAM, disco y uptime.

---

## Características principales

- Panel administrativo con Filament.
- Registro de equipos monitoreados.
- Registro de servicios TCP asociados a cada equipo.
- Monitoreo de puertos TCP mediante comando Artisan.
- Ejecución automática mediante Laravel Scheduler.
- Historial de chequeos de servicios.
- Generación de alertas cuando un servicio deja de responder.
- Resolución automática de alertas cuando el servicio vuelve a estar disponible.
- Notificaciones automáticas vía Telegram para alertas y recuperaciones.
- Agente Python para recolectar métricas del sistema.
- Agente configurable mediante archivo `.env`.
- Agente con ejecución manual y continua.
- Agente con logs locales.
- Agente con reintentos automáticos.
- Agente con modo `verbose` y modo `silent`.
- API REST para recepción de métricas.
- Dashboard con estadísticas generales.
- Dashboard con gráficas de CPU, RAM y disco.
- Tabla de últimas métricas recibidas.
- Tabla de servicios caídos.
- Tabla de hosts con mayor uso de recursos.
- Panel de control para ejecutar monitoreo TCP y agente Python desde Filament.
- Botones para iniciar y detener procesos automáticos desde el panel.
- Sistema de roles: administrador, técnico y observador.
- Control de permisos por módulo mediante Policies.
- Limpieza automática de historial antiguo.
- Comando `monitor:cleanup` para mantenimiento de métricas y chequeos.
- Actualización en tiempo real con WebSocket.
- Integración con Laravel Reverb.
- Eventos en vivo para métricas, chequeos y alertas.
- Tests automatizados básicos.
- Integración continua con GitHub Actions.
- Docker Compose completo para backend, PostgreSQL, Scheduler y Reverb.
- Queue worker preparado como servicio opcional.
- Documentación para instalar el agente como servicio Linux.

---

## Tecnologías utilizadas

| Área | Tecnología |
|---|---|
| Backend | Laravel |
| Panel administrativo | Filament |
| Base de datos | PostgreSQL |
| Contenedores | Docker / Docker Compose |
| Agente | Python |
| Métricas del sistema | psutil |
| Variables de entorno del agente | python-dotenv |
| Comunicación HTTP | requests |
| Notificaciones | Telegram Bot API |
| WebSocket | Laravel Reverb |
| Frontend realtime | Laravel Echo / Pusher JS |
| Tests | PHPUnit / Laravel Test |
| CI | GitHub Actions |
| Control de versiones | Git / GitHub |

---

## Arquitectura general

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
| API + Comandos Artisan  |
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
| Estado / Gráficas       |
| Alertas / Historial     |
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

## Documentación

- [Arquitectura](docs/architecture.md)
- [API](docs/api.md)
- [Notificaciones por Telegram](docs/telegram.md)
- [Docker Compose](docs/docker.md)
- [Instalación del agente como servicio Linux](docs/agent-service-linux.md)
- [Actualización en tiempo real con WebSocket](docs/realtime-websockets.md)

---

## Capturas del sistema

### Dashboard principal

![Dashboard](docs/screenshots/dashboard.png)

### Equipos monitoreados

![Monitored Hosts](docs/screenshots/hosts.png)

### Servicios monitoreados

![Monitored Services](docs/screenshots/services.png)

### Historial de chequeos

![Service Checks](docs/screenshots/service-checks.png)

### Métricas del host

![Host Metrics](docs/screenshots/host-metrics.png)

### Alertas

![Alerts](docs/screenshots/alerts.png)

### Alertas vía Telegram

![Telegram Alerts](docs/screenshots/telegram.jpg)

> Si alguna imagen no aparece en GitHub, verificar que exista dentro de `docs/screenshots/` y que el nombre del archivo coincida exactamente.

---

## Estructura del proyecto

```text
infrawatch-monitoring-system/
├── .github/
│   └── workflows/
│       └── backend-ci.yml
│
├── backend/
│   ├── app/
│   │   ├── Console/
│   │   ├── Events/
│   │   ├── Filament/
│   │   ├── Http/
│   │   ├── Models/
│   │   ├── Policies/
│   │   └── Services/
│   ├── config/
│   ├── database/
│   ├── docker/
│   ├── resources/
│   ├── routes/
│   ├── tests/
│   ├── docker-compose.yml
│   ├── .env.example
│   └── README.md
│
├── agent/
│   ├── agent.py
│   ├── requirements.txt
│   ├── .env.example
│   └── README.md
│
├── docs/
│   ├── architecture.md
│   ├── api.md
│   ├── telegram.md
│   ├── docker.md
│   ├── agent-service-linux.md
│   ├── realtime-websockets.md
│   └── screenshots/
│
├── .gitignore
└── README.md
```

---

## Módulos principales

| Módulo | Descripción |
|---|---|
| Monitored Hosts | Registro de equipos monitoreados. |
| Monitored Services | Servicios TCP asociados a cada equipo. |
| Service Checks | Historial de revisiones de disponibilidad. |
| Host Metrics | Métricas enviadas por el agente Python. |
| Alerts | Alertas generadas por fallos detectados. |
| Dashboard | Vista general del estado de la infraestructura. |
| Monitoring Control | Panel para iniciar, detener y ejecutar procesos de monitoreo. |
| Users | Administración de usuarios y roles. |
| Telegram Notifications | Envío de alertas y recuperaciones vía Telegram. |
| Realtime Events | Actualización del panel mediante WebSocket. |

---

## Instalación rápida local

### 1. Clonar el repositorio

```bash
git clone https://github.com/Josue-Isai-Sanchez-Santos/infrawatch-monitoring-system.git
cd infrawatch-monitoring-system
```

### 2. Instalar backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
docker compose up -d postgres
php artisan migrate
php artisan make:filament-user
php artisan serve
```

Panel administrativo:

```text
http://127.0.0.1:8000/admin
```

### 3. Instalar agente Python

En otra terminal:

```bash
cd agent
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
cp .env.example .env
```

Editar `agent/.env`:

```env
API_URL=http://127.0.0.1:8000/api/agent/metrics
AGENT_TOKEN=your-agent-token-here
INTERVAL_SECONDS=60
REQUEST_TIMEOUT_SECONDS=5
RETRY_ATTEMPTS=3
RETRY_DELAY_SECONDS=3
LOG_FILE=logs/agent.log
```

Ejecutar el agente una sola vez:

```bash
python agent.py --once
```

Ejecutar el agente de forma continua:

```bash
python agent.py --interval 60
```

---

## Levantar con Docker Compose

Desde `backend`:

```bash
docker compose up -d --build
```

Esto levanta:

- Laravel app
- PostgreSQL
- Scheduler
- Reverb

Queue worker opcional:

```bash
docker compose --profile queue up -d
```

---

## Comandos principales

### Ejecutar monitoreo TCP manual

```bash
cd backend
php artisan monitor:services
```

### Ejecutar scheduler en desarrollo

```bash
cd backend
php artisan schedule:work
```

### Limpiar historial antiguo de monitoreo

```bash
cd backend
php artisan monitor:cleanup --days=30
```

### Simular limpieza sin borrar datos

```bash
cd backend
php artisan monitor:cleanup --days=30 --dry-run
```

### Ejecutar Reverb

```bash
cd backend
php artisan reverb:start --host=0.0.0.0 --port=8080 --debug
```

### Ejecutar tests

```bash
cd backend
php artisan test
```

### Formatear código PHP

```bash
cd backend
./vendor/bin/pint
```

### Ver rutas registradas

```bash
cd backend
php artisan route:list
```

---

## API

InfraWatch cuenta con una API para recibir métricas desde agentes externos.

Endpoint principal:

```http
POST /api/agent/metrics
```

Documentación completa:

[Ver documentación de API](docs/api.md)

---

## Notificaciones por Telegram

InfraWatch puede enviar notificaciones automáticas a Telegram cuando un servicio cae o vuelve a estar disponible.

Variables requeridas en `backend/.env`:

```env
TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=your-telegram-bot-token
TELEGRAM_CHAT_ID=your-chat-id
```

Documentación completa:

[Ver documentación de Telegram](docs/telegram.md)

---

## Actualización en tiempo real

InfraWatch usa Laravel Reverb para transmitir eventos al panel administrativo.

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

Documentación completa:

[Ver documentación de WebSocket](docs/realtime-websockets.md)

---

## Roles y permisos

| Rol | Permisos |
|---|---|
| Administrador | Acceso total al sistema, usuarios, monitoreo, recursos y eliminación de registros. |
| Técnico | Puede revisar infraestructura, crear/editar hosts y servicios, consultar métricas y resolver alertas. |
| Observador | Solo lectura sobre dashboard, hosts, servicios, métricas, chequeos y alertas. |

La página `Monitoring Control` y la administración de usuarios están restringidas al rol administrador.

---

## Integración continua

El proyecto incluye un workflow de GitHub Actions para validar el backend automáticamente.

Archivo:

```text
.github/workflows/backend-ci.yml
```

El workflow ejecuta:

- Instalación de dependencias con Composer.
- Configuración de PHP.
- Servicio PostgreSQL para pruebas.
- Migraciones de base de datos.
- Tests automatizados.
- Revisión de rutas registradas.

Se ejecuta automáticamente en cada `push` o `pull request` hacia `main` o `master`.

---

## Estado actual

**Versión actual: V2.0 cerrada**

Funcionalidades implementadas:

- Backend Laravel funcional.
- Panel administrativo con Filament.
- Base de datos PostgreSQL.
- Docker Compose completo.
- CRUD de equipos monitoreados.
- CRUD de servicios monitoreados.
- Comando de monitoreo TCP.
- Scheduler de Laravel.
- Historial de chequeos.
- Dashboard con estadísticas generales.
- Dashboard con gráficas de CPU, RAM y disco.
- Tabla de últimas métricas recibidas.
- Tabla de hosts con mayor uso de recursos.
- Tabla de servicios caídos.
- Alertas básicas.
- Resolución automática de alertas.
- Notificaciones vía Telegram.
- Agente Python para métricas del sistema.
- Agente configurable mediante `.env`.
- Agente con logs, reintentos, modo `verbose` y modo `silent`.
- API para recepción de métricas.
- Panel de control para ejecutar procesos manuales y automáticos.
- Botones para iniciar y detener procesos automáticos.
- Roles y permisos.
- Limpieza automática de métricas y chequeos antiguos.
- Tests básicos de API, modelos y comando de monitoreo.
- GitHub Actions para CI.
- WebSocket con Laravel Reverb.
- Dashboard actualizado en tiempo real.

---

## Próximas mejoras

- Reportes PDF.
- Notificaciones por correo electrónico.
- Métricas de red en el agente.
- Métricas de temperatura si el hardware lo permite.
- Comandos desde Telegram para consultar estado.
- Sistema multiempresa o multisede.
- Deploy en VPS o servidor local.
- Producción con Nginx/Caddy y PHP-FPM.
- Actualización granular de widgets sin recargar toda la página.

---

## Seguridad

El archivo `.env` no debe subirse al repositorio.

Archivos que sí pueden subirse:

```text
.env.example
```

Credenciales que deben mantenerse privadas:

- `APP_KEY`
- `DB_PASSWORD` si se usa una contraseña real.
- `AGENT_TOKEN`
- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_CHAT_ID`
- `REVERB_APP_SECRET`

---

## Autor

Desarrollado por **Josue Isai Sanchez Santos** como proyecto de portafolio técnico enfocado en sistemas, redes, monitoreo e infraestructura TI.


```markdown
## Licencia

Este proyecto se distribuye bajo la licencia MIT. Consulta el archivo [LICENSE](LICENSE) para obtener más información.
```
