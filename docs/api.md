# API de InfraWatch

InfraWatch expone una API REST para recibir métricas enviadas por agentes externos.

Actualmente la API principal permite que un agente Python envíe métricas básicas de un equipo monitoreado.

---

## Base URL

En desarrollo local:

```text
http://127.0.0.1:8000
```

---

## Autenticación del agente

El agente debe enviar un token tipo Bearer Token.

Header requerido:

```http
Authorization: Bearer AGENT_TOKEN
Accept: application/json
```

El valor de `AGENT_TOKEN` debe coincidir con el campo `agent_token` registrado en el equipo dentro del panel de administración.

---

# Endpoints

---

## Enviar métricas del agente

Envía métricas de un equipo monitoreado al backend.

```http
POST /api/agent/metrics
```

---

## Headers

```http
Authorization: Bearer AGENT_TOKEN
Accept: application/json
Content-Type: application/json
```

---

## Body

```json
{
  "hostname": "server-local",
  "ip_address": "127.0.0.1",
  "operating_system": "Linux 6.x",
  "cpu_usage": 25.5,
  "ram_usage": 60.2,
  "disk_usage": 70.1,
  "uptime_seconds": 123456
}
```

---

## Campos del body

| Campo | Tipo | Requerido | Descripción |
|---|---|---:|---|
| `hostname` | string | Sí | Nombre del equipo. |
| `ip_address` | string | Sí | Dirección IP del equipo. |
| `operating_system` | string | No | Sistema operativo detectado. |
| `cpu_usage` | number | Sí | Porcentaje de uso de CPU. |
| `ram_usage` | number | Sí | Porcentaje de uso de RAM. |
| `disk_usage` | number | Sí | Porcentaje de uso de disco. |
| `uptime_seconds` | integer | No | Tiempo encendido del sistema en segundos. |

---

## Respuesta exitosa

Código:

```http
201 Created
```

Respuesta:

```json
{
  "message": "Metrics stored successfully.",
  "metric_id": 1
}
```

---

## Error: token faltante

Código:

```http
401 Unauthorized
```

Respuesta:

```json
{
  "message": "Missing agent token."
}
```

---

## Error: token inválido

Código:

```http
403 Forbidden
```

Respuesta:

```json
{
  "message": "Invalid agent token."
}
```

---

## Error: validación de datos

Código:

```http
422 Unprocessable Content
```

Ejemplo de respuesta:

```json
{
  "message": "The cpu usage field is required.",
  "errors": {
    "cpu_usage": [
      "The cpu usage field is required."
    ]
  }
}
```

---

## Ejemplo con curl

```bash
curl -X POST http://127.0.0.1:8000/api/agent/metrics \
  -H "Authorization: Bearer local-agent-token-123" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "hostname": "server-local",
    "ip_address": "127.0.0.1",
    "operating_system": "Linux 6.x",
    "cpu_usage": 25.5,
    "ram_usage": 60.2,
    "disk_usage": 70.1,
    "uptime_seconds": 123456
  }'
```

---

## Ejemplo desde Python

```python
import requests

API_URL = "http://127.0.0.1:8000/api/agent/metrics"
AGENT_TOKEN = "local-agent-token-123"

headers = {
    "Authorization": f"Bearer {AGENT_TOKEN}",
    "Accept": "application/json",
}

payload = {
    "hostname": "server-local",
    "ip_address": "127.0.0.1",
    "operating_system": "Linux 6.x",
    "cpu_usage": 25.5,
    "ram_usage": 60.2,
    "disk_usage": 70.1,
    "uptime_seconds": 123456,
}

response = requests.post(API_URL, json=payload, headers=headers)

print(response.status_code)
print(response.json())
```

---

## Lógica interna del endpoint

Cuando el backend recibe una petición:

1. Obtiene el Bearer Token.
2. Valida que el token exista.
3. Busca un equipo en `monitored_hosts` con ese `agent_token`.
4. Si no existe, responde `403`.
5. Valida los datos recibidos.
6. Actualiza información básica del equipo.
7. Marca el equipo como `online`.
8. Actualiza `last_seen_at`.
9. Guarda las métricas en `host_metrics`.
10. Responde con el ID del registro creado.

---

## Tabla relacionada: monitored_hosts

El token se valida contra:

```text
monitored_hosts.agent_token
```

El equipo debe estar registrado previamente desde el panel administrativo.

---

## Tabla relacionada: host_metrics

Cada petición correcta crea un registro en:

```text
host_metrics
```

Campos guardados:

- `monitored_host_id`
- `cpu_usage`
- `ram_usage`
- `disk_usage`
- `uptime_seconds`
- `recorded_at`

---

## Consideraciones de seguridad

- No subir tokens reales al repositorio.
- No compartir el archivo `.env`.
- Usar HTTPS en producción.
- Generar tokens largos y difíciles de adivinar.
- Cambiar tokens si se sospecha exposición.
- Validar origen de agentes en versiones futuras.

---

## Mejoras futuras de la API

- Endpoint para registrar agentes automáticamente.
- Endpoint para heartbeat.
- Endpoint para enviar logs del agente.
- Endpoint para métricas de red.
- Autenticación con tokens rotativos.
- Rate limiting.
- Versionado de API, por ejemplo `/api/v1/agent/metrics`.
