# Agente Python: instalación, operación y pruebas

El agente se ejecuta en cada equipo a observar y envía métricas del sistema a la API de InfraWatch. Implementación: `agent/agent.py`.

## Requisitos y preparación

```bash
cd infrawatch-monitoring-system
python3 -m venv agent/.venv
agent/.venv/bin/python -m pip install -r agent/requirements.txt
cp agent/.env.example agent/.env
```

Edita `agent/.env`: `API_URL` debe apuntar al backend accesible **desde el host del agente**; `AGENT_TOKEN` es un secreto específico del host. La URL de `127.0.0.1` sirve solo si el backend se ejecuta en la misma computadora o existe un túnel intencional.

## Modos de ejecución

```bash
agent/.venv/bin/python agent/agent.py --once
agent/.venv/bin/python agent/agent.py --interval 60
agent/.venv/bin/python agent/agent.py --once --verbose
agent/.venv/bin/python agent/agent.py --interval 60 --silent
```

- Sin argumentos: una ejecución y salida.
- `--interval 60`: bucle continuo; sin número usa `INTERVAL_SECONDS`.
- `--verbose`: `DEBUG` en archivo y consola.
- `--silent`: elimina salida en consola y mantiene logs rotativos en archivo.
- SIGTERM/SIGINT: parada limpia y sin nueva solicitud tras la señal.

## Métricas y reintentos

El payload incluye `reading_id` UUID, hostname, IP, SO, CPU/RAM/disco y uptime. El UUID se conserva entre los reintentos de una misma lectura, de forma que un retry no duplica muestras aceptadas. Se reintentan timeouts, errores de conexión, HTTP 429 y 5xx; no se reintentan errores de certificado TLS ni 4xx permanentes. La espera para 429 respeta `Retry-After` con límites.

## Logs y secretos

El agente usa `RotatingFileHandler`, tamaño y número de backups configurables. `silent` **no** desactiva archivos de logs. No publiques logs que contengan detalles de la red; no imprimas el token de agente ni subas `.env`.

## Pruebas

```bash
agent/.venv/bin/python -m unittest discover -s agent/tests -p 'test_*.py' -v
```

Si aparece `ModuleNotFoundError: psutil`, probablemente estás usando el Python global en lugar del entorno virtual; instala dependencias en el venv. La CI utiliza Python 3.12, que se debe emplear si buscas equivalencia de entorno.

Para Linux `systemd`, consulta [agent-service-linux.md](agent-service-linux.md). Para el servidor y las validaciones de backend, [API](api.md) y [testing-ci](testing-ci.md).
