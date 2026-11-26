# Instalación local de InfraWatch

Esta guía es para **desarrollo en WSL/Linux** con Docker Compose. No debe interpretarse como procedimiento de despliegue público endurecido.

## Requisitos

- Git y Docker Engine/Desktop con Compose v2 y daemon funcionando.
- Acceso a Internet para imágenes y dependencias (Composer y npm).
- Espacio para datos de PostgreSQL y almacenamiento persistente.
- Para ejecutar Python fuera de Docker, intérprete compatible con las dependencias de `agent/requirements.txt`; CI utiliza Python 3.12.
- PHP local opcional solo si no usas contenedor; `composer.json` exige PHP `>=8.4.1 <8.6`.

## 1. Obtener el código

```bash
git clone https://github.com/Josue-Isai-Sanchez-Santos/infrawatch-monitoring-system.git
cd infrawatch-monitoring-system
git status -sb
```

## 2. Preparar credenciales locales

```bash
cd backend
cp .env.example .env
cp .env.docker.example .env.docker
```

Haz estas copias **solo en una instalación nueva**. En instalaciones existentes preserva `.env` y `.env.docker`. Cambia la contraseña de PostgreSQL y los secretos de Reverb del archivo `.env.docker`; no reutilices los valores públicos de ejemplo. En el entorno del proyecto, los contenedores usan `DB_HOST=postgres` y la contraseña de `.env.docker`. Fuera del contenedor, Laravel puede usar `127.0.0.1` y requiere credenciales que correspondan a una cuenta existente.

## 3. Levantar contenedores

```bash
docker compose --env-file .env.docker config --quiet
docker compose --env-file .env.docker up -d --build
docker compose --env-file .env.docker ps
```

Si no arranca, consulta `docker compose --env-file .env.docker logs --tail=100 app postgres vite` y [diagnóstico](troubleshooting.md).

## 4. Generar clave, comprobar BD, crear cuenta

```bash
docker compose --env-file .env.docker exec app php artisan key:generate
docker compose --env-file .env.docker exec app php artisan migrate:status
docker compose --env-file .env.docker exec app php artisan make:filament-user
```

El comando de Filament crea un usuario; confirma que tenga `role=admin` de manera controlada si el creador de usuarios no lo asigna. Los roles no se elevan automáticamente por usar la página de login. El panel está en `http://127.0.0.1:8000/admin`.

## 5. Agente de métricas

```bash
cd ..
python3 -m venv agent/.venv
agent/.venv/bin/python -m pip install -r agent/requirements.txt
cp agent/.env.example agent/.env
# Ajusta API_URL y AGENT_TOKEN con un token exclusivo del host
agent/.venv/bin/python agent/agent.py --once
```

El agente se ejecuta donde se quieren recolectar métricas. Un token de un host no debe compartirse entre máquinas.

## 6. Pruebas de instalación

```bash
cd backend
docker compose --env-file .env.docker exec app php artisan route:list
docker compose --env-file .env.docker exec app ./vendor/bin/pint --test
docker compose --env-file .env.docker exec app composer audit --locked --no-interaction
docker compose --env-file .env.docker exec vite npm run build
```

La CI usa PostgreSQL; si ejecutas tests locales con SQLite en memoria sigue [testing-ci](testing-ci.md). Los comandos de migración/reinstalación destructiva no forman parte de una instalación segura.

## 7. Desconexión y datos

```bash
docker compose --env-file .env.docker down
```

No uses `down -v` salvo que **intencionalmente** quieras destruir volúmenes. Al actualizar dependencias, respalda datos y verifica los cambios antes de ejecutar migraciones nuevas.
