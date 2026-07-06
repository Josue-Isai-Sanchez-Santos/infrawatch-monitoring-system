# InfraWatch Monitoring System

InfraWatch es un sistema web de monitoreo de infraestructura TI desarrollado con Laravel, Filament, PostgreSQL y un agente Python. Su objetivo es registrar equipos, monitorear servicios de red, recolectar métricas del sistema y mostrar alertas desde un panel administrativo.

Este proyecto está pensado como una solución base para supervisar servidores, estaciones de trabajo, servicios TCP y métricas básicas como CPU, RAM, disco y uptime.

---

## Características principales

- Panel administrativo con Filament.
- Registro de equipos monitoreados.
- Registro de servicios asociados a cada equipo.
- Monitoreo de puertos TCP mediante comando Artisan.
- Scheduler para ejecutar revisiones automáticas.
- Historial de chequeos de servicios.
- Generación de alertas cuando un servicio deja de responder.
- Resolución automática de alertas cuando el servicio vuelve a estar disponible.
- Agente Python para recolectar métricas del sistema.
- Recepción de métricas mediante API REST.
- Dashboard con estadísticas generales.
- Base de datos PostgreSQL.
- Entorno local con Docker.

---

## Tecnologías utilizadas

### Backend

- PHP
- Laravel
- Filament
- PostgreSQL
- Docker
- Composer

### Agente

- Python
- psutil
- requests

### Herramientas

- Git
- GitHub
- WSL Ubuntu
- Visual Studio Code

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
Arquitectura general

InfraWatch se divide en dos módulos principales:

Backend Laravel
Administra equipos, servicios, métricas, alertas e historial.
Expone una API para recibir métricas desde agentes externos.
Ejecuta chequeos de servicios mediante comandos programados.
Agente Python
Se instala en el equipo que se desea monitorear.
Recolecta información de CPU, RAM, disco, hostname, IP, sistema operativo y uptime.
Envía las métricas al backend mediante HTTP POST.

Flujo general:

Equipo monitoreado
    ↓
Agente Python recolecta métricas
    ↓
API Laravel recibe datos
    ↓
PostgreSQL almacena métricas e historial
    ↓
Filament muestra dashboard, alertas y reportes
Capturas del sistema
Dashboard

Equipos monitoreados

Servicios monitoreados

Historial de chequeos

Métricas del host

Alertas

Instalación del backend

Entrar a la carpeta del backend:

cd backend

Instalar dependencias de PHP:

composer install

Copiar archivo de entorno:

cp .env.example .env

Generar llave de aplicación:

php artisan key:generate

Levantar PostgreSQL con Docker:

docker compose up -d

Ejecutar migraciones:

php artisan migrate

Crear usuario administrador de Filament:

php artisan make:filament-user

Levantar servidor local:

php artisan serve

Acceder al panel:

http://127.0.0.1:8000/admin
Instalación del agente Python

Entrar a la carpeta del agente:

cd agent

Crear entorno virtual:

python3 -m venv venv

Activar entorno virtual:

source venv/bin/activate

Instalar dependencias:

pip install -r requirements.txt

Configurar el token del agente dentro de agent.py o mediante archivo .env, dependiendo de la versión implementada.

Ejecutar el agente:

python agent.py
Comandos principales

Ejecutar monitoreo manual de servicios:

php artisan monitor:services

Ejecutar scheduler en desarrollo:

php artisan schedule:work

Ejecutar tareas programadas una sola vez:

php artisan schedule:run

Limpiar caché de Laravel:

php artisan optimize:clear

Ver rutas registradas:

php artisan route:list
Módulos principales
Monitored Hosts

Permite registrar equipos que serán monitoreados.

Campos principales:

Nombre
Hostname
Dirección IP
Sistema operativo
Tipo de equipo
Ubicación
Estado
Token del agente
Última conexión
Monitored Services

Permite registrar servicios asociados a un equipo.

Campos principales:

Equipo
Nombre del servicio
Puerto
Protocolo
Estado
Última revisión
Service Checks

Guarda el historial de revisiones realizadas por el comando de monitoreo.

Campos principales:

Servicio
Estado
Tiempo de respuesta
Mensaje
Fecha de revisión
Host Metrics

Guarda las métricas enviadas por el agente Python.

Campos principales:

Equipo
Uso de CPU
Uso de RAM
Uso de disco
Uptime
Fecha de registro
Alerts

Registra alertas generadas por fallos detectados.

Campos principales:

Equipo
Servicio
Tipo
Severidad
Título
Mensaje
Estado
Fecha de activación
Fecha de resolución
Estado actual del proyecto

Versión actual: V1 en desarrollo

Funcionalidades implementadas:

Backend Laravel funcional.
Panel administrativo con Filament.
Base de datos PostgreSQL en Docker.
CRUD de equipos monitoreados.
CRUD de servicios monitoreados.
Comando de monitoreo TCP.
Scheduler de Laravel.
Historial de chequeos.
Dashboard con estadísticas.
Alertas básicas.
Agente Python para métricas del sistema.
API para recepción de métricas.
Próximas mejoras
Gráficas históricas de CPU, RAM y disco.
Notificaciones por correo electrónico.
Notificaciones por Telegram.
Roles y permisos.
Reportes PDF.
Limpieza automática de métricas antiguas.
Docker Compose completo para backend, base de datos y servicios auxiliares.
Tests automatizados.
GitHub Actions para validación del proyecto.
Deploy en VPS o servidor local.
Seguridad

El archivo .env no debe subirse al repositorio.

Solo deben subirse archivos de ejemplo como:

.env.example

El token real del agente debe mantenerse privado.

Autor

Proyecto desarrollado por Josue Sanchez como parte de su portafolio técnico en sistemas, redes, monitoreo e infraestructura TI.
