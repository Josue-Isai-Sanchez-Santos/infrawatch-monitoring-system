# InfraWatch Backend

Backend principal de InfraWatch desarrollado con Laravel, Filament y PostgreSQL.

Este módulo se encarga de administrar equipos monitoreados, servicios, métricas, alertas, historial de chequeos y API para agentes externos.

---

## Tecnologías

- PHP
- Laravel
- Filament
- PostgreSQL
- Docker
- Composer

---

## Requisitos

- PHP compatible con la versión del proyecto Laravel.
- Composer.
- Docker.
- PostgreSQL mediante Docker Compose.
- Node.js y NPM si se requiere compilar assets.

---

## Instalación

Entrar a la carpeta del backend:

```bash
cd backend

Instalar dependencias:

composer install

Copiar archivo de entorno:

cp .env.example .env

Generar llave de aplicación:

php artisan key:generate

Levantar PostgreSQL:

docker compose up -d

Ejecutar migraciones:

php artisan migrate

Crear usuario administrador para Filament:

php artisan make:filament-user

Levantar servidor local:

php artisan serve

Acceder al panel:

http://127.0.0.1:8000/admin
Configuración de base de datos

Configuración esperada en .env para desarrollo local:

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=infrawatch
DB_USERNAME=infrawatch_user
DB_PASSWORD=infrawatch_password
Docker Compose

El backend incluye un archivo docker-compose.yml para levantar PostgreSQL localmente.

Comando para iniciar:

docker compose up -d

Comando para detener:

docker compose down

Comando para revisar contenedores:

docker ps
Panel administrativo

El panel administrativo está construido con Filament.

Ruta local:

/admin

Módulos principales:

Monitored Hosts
Monitored Services
Host Metrics
Service Checks
Alerts
Comando de monitoreo de servicios

InfraWatch incluye un comando Artisan para revisar servicios TCP registrados.

Ejecutar manualmente:

php artisan monitor:services

El comando realiza lo siguiente:

Lee todos los servicios registrados.
Obtiene la IP del equipo asociado.
Intenta conectarse al puerto configurado.
Mide el tiempo de respuesta.
Guarda un registro en service_checks.
Actualiza el estado del servicio.
Actualiza el estado del equipo.
Genera una alerta si el servicio no responde.
Resuelve alertas abiertas si el servicio vuelve a responder.
Scheduler

Para ejecutar el monitoreo automáticamente se usa el scheduler de Laravel.

En desarrollo:

php artisan schedule:work

Para ejecutar las tareas programadas una sola vez:

php artisan schedule:run

La programación se configura en:

routes/console.php

Ejemplo:

use Illuminate\Support\Facades\Schedule;

Schedule::command('monitor:services')->everyMinute();
API

El backend expone una API para recibir métricas del agente Python.

Endpoint:

POST /api/agent/metrics

Más información en:

docs/api.md
Modelos principales
MonitoredHost

Representa un equipo monitoreado.

Relaciones:

Tiene muchos servicios.
Tiene muchas métricas.
Tiene muchas alertas.
MonitoredService

Representa un servicio TCP asociado a un equipo.

Relaciones:

Pertenece a un equipo.
Tiene muchos chequeos.
Tiene muchas alertas.
ServiceCheck

Representa un registro histórico de revisión de un servicio.

HostMetric

Representa una métrica enviada por un agente.

Alert

Representa una alerta generada por una falla o evento importante.

Comandos útiles

Limpiar caché:

php artisan optimize:clear

Ver rutas:

php artisan route:list

Ver migraciones:

php artisan migrate:status

Recrear base de datos en desarrollo:

php artisan migrate:fresh
Seguridad

No subir el archivo .env.

El archivo .env.example sí puede subirse porque solo contiene valores de ejemplo.

El token real del agente debe mantenerse privado.

