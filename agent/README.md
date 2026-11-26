# Agente Python de InfraWatch

Cliente Python para enviar métricas locales a `POST /api/agent/metrics` de InfraWatch. **No necesita credenciales de usuarios del panel:** utiliza un token Bearer por host.

## Qué mide y envía

`hostname`, `ip_address`, `operating_system`, `cpu_usage`, `ram_usage`, `disk_usage`, `uptime_seconds` y un `reading_id` UUID para idempotencia. Implementación: [`agent.py`](agent.py).

## Instalación aislada

```bash
# Desde la raíz del repositorio
python3 -m venv agent/.venv
agent/.venv/bin/python -m pip install -r agent/requirements.txt
cp agent/.env.example agent/.env
```

Edita `agent/.env` con tu URL de API y un token válido asignado al host. **Nunca publiques ese archivo**. La configuración admite `INTERVAL_SECONDS`, `REQUEST_TIMEOUT_SECONDS`, `RETRY_ATTEMPTS`, `RETRY_DELAY_SECONDS`, `LOG_FILE`, `LOG_MAX_BYTES` y `LOG_BACKUP_COUNT`.

```bash
agent/.venv/bin/python agent/agent.py --once
agent/.venv/bin/python agent/agent.py --interval 60
agent/.venv/bin/python agent/agent.py --interval 60 --silent
agent/.venv/bin/python agent/agent.py --once --verbose
agent/.venv/bin/python agent/agent.py --help
```

Sin `--interval` realiza **una sola lectura**. `--silent` inhibe la salida en consola pero deja registro en archivo. `--verbose` activa eventos `DEBUG`. Los logs rotan según tamaño y número de copias de la configuración.

## Reintentos e idempotencia

El agente reintenta fallos de red, timeouts, respuestas HTTP 5xx y HTTP 429 (respeta `Retry-After` con límites). **No** reintenta errores permanentes 4xx distintos de 429 ni fallos de certificado TLS. Reutiliza el mismo `reading_id` entre intentos para evitar métricas duplicadas. Interrumpe el ciclo cuando recibe señal de parada.

## Pruebas

```bash
# Desde la raíz
agent/.venv/bin/python -m unittest discover -s agent/tests -p 'test_*.py' -v
```

Las pruebas usan `unittest.mock` y no envían métricas reales. La CI configura Python 3.12; ejecutar con un Python diferente puede revelar incompatibilidades adicionales. Dependencias fijadas en `requirements.txt`.

## Servicio Linux y seguridad

Consulta [systemd](../docs/agent-service-linux.md) y [operación del agente](../docs/agent-operations.md). No registres el token en logs; para rotarlo actualiza el token del host y `agent/.env` en coordinación para no interrumpir envíos. Evita publicar la API HTTP en redes no confiables sin HTTPS y autenticación configurada.
