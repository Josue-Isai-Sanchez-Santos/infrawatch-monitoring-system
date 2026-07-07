# InfraWatch Monitoring System

<p align="center">
  <strong>Sistema web de monitoreo de infraestructura TI</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-Backend-red?style=for-the-badge&logo=laravel" />
  <img src="https://img.shields.io/badge/Filament-Admin%20Panel-orange?style=for-the-badge" />
  <img src="https://img.shields.io/badge/PostgreSQL-Database-blue?style=for-the-badge&logo=postgresql" />
  <img src="https://img.shields.io/badge/Python-Agent-green?style=for-the-badge&logo=python" />
</p>

---

## Descripción

**InfraWatch** es un sistema web de monitoreo de infraestructura TI desarrollado con **Laravel**, **Filament**, **PostgreSQL** y un **agente Python**.

El sistema permite registrar equipos, monitorear servicios de red por puerto TCP, recolectar métricas del sistema y visualizar alertas desde un panel administrativo.

Está pensado como una solución base para supervisar servidores, estaciones de trabajo, servicios internos y métricas básicas como CPU, RAM, disco y uptime.

---

## Características principales

- Panel administrativo con Filament.
- Registro de equipos monitoreados.
- Registro de servicios asociados a cada equipo.
- Monitoreo de puertos TCP mediante comando Artisan.
- Ejecución automática mediante Laravel Scheduler.
- Historial de chequeos de servicios.
- Generación de alertas cuando un servicio deja de responder.
- Resolución automática de alertas cuando el servicio vuelve a estar disponible.
- Agente Python para recolectar métricas del sistema.
- API REST para recepción de métricas.
- Dashboard con estadísticas generales.
- Base de datos PostgreSQL.
- Entorno local con Docker.

---

## Tecnologías utilizadas

| Área | Tecnología |
|---|---|
| Backend | Laravel |
| Panel administrativo | Filament |
| Base de datos | PostgreSQL |
| Contenedores | Docker |
| Agente | Python |
| Métricas del sistema | psutil |
| Comunicación HTTP | requests |
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
| Estado general          |
| Alertas e historial     |
+-------------------------+
```

Documentación completa de arquitectura:

[Ver arquitectura](docs/architecture.md)

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

---

## Estructura del proyecto

```text
infrawatch-monitoring-system/
├── backend/
│   ├── app/
│   ├── database/
│   ├── routes/
│   ├── docker-compose.yml
│   └── README.md
│
├── agent/
│   ├── agent.py
│   ├── requirements.txt
│   └── README.md
│
├── docs/
│   ├── architecture.md
│   ├── api.md
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

---

## Instalación rápida

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
docker compose up -d
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
python agent.py
```

---

## Comandos principales

### Ejecutar monitoreo manual de servicios

```bash
php artisan monitor:services
```

### Ejecutar scheduler en desarrollo

```bash
php artisan schedule:work
```

### Ejecutar tareas programadas una sola vez

```bash
php artisan schedule:run
```

### Limpiar caché de Laravel

```bash
php artisan optimize:clear
```

### Ver rutas registradas

```bash
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

## Estado actual

Versión actual: **V1 en desarrollo**

Funcionalidades implementadas:

- Backend Laravel funcional.
- Panel administrativo con Filament.
- Base de datos PostgreSQL en Docker.
- CRUD de equipos monitoreados.
- CRUD de servicios monitoreados.
- Comando de monitoreo TCP.
- Scheduler de Laravel.
- Historial de chequeos.
- Dashboard con estadísticas.
- Alertas básicas.
- Agente Python para métricas del sistema.
- API para recepción de métricas.

---

## Próximas mejoras

- Gráficas históricas de CPU, RAM y disco.
- Notificaciones por correo electrónico.
- Notificaciones por Telegram.
- Roles y permisos.
- Reportes PDF.
- Limpieza automática de métricas antiguas.
- Docker Compose completo para backend, base de datos y servicios auxiliares.
- Tests automatizados.
- GitHub Actions.
- Deploy en VPS o servidor local.

---

## Seguridad

El archivo `.env` no debe subirse al repositorio.

Solo deben subirse archivos de ejemplo como:

```text
.env.example
```

El token real del agente debe mantenerse privado.

---

## Autor

Desarrollado por **Josue Isai Sanchez Santos** como proyecto de portafolio técnico enfocado en sistemas, redes, monitoreo e infraestructura TI.
