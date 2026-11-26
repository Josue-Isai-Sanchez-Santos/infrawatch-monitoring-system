# API REST de InfraWatch — recepción de métricas

**Endpoint implementado:** `POST /api/agent/metrics`. Archivos fuente: `backend/routes/api.php` y `backend/app/Http/Controllers/Api/AgentMetricController.php`. Este endpoint sirve para agentes autorizados, no es una API pública de inventario.

## Autenticación y transporte

Cabecera obligatoria:

```http
Authorization: Bearer <TOKEN_EXCLUSIVO_DEL_HOST>
Accept: application/json
Content-Type: application/json
```

El token se compara con el atributo privado `monitored_hosts.agent_token`. No publiques ese token en Issues, ejemplos del repo, shell history ni capturas. El Compose local puede usar HTTP para laboratorio; **para redes no confiables utiliza HTTPS**.

## Solicitud JSON

```json
{
  "reading_id": "550e8400-e29b-41d4-a716-446655440000",
  "hostname": "example-host",
  "ip_address": "192.0.2.10",
  "operating_system": "Linux",
  "cpu_usage": 25.5,
  "ram_usage": 60.2,
  "disk_usage": 70.1,
  "uptime_seconds": 123456
}
```

| Campo | Tipo | Validaciones |
|---|---|---|
| `reading_id` | UUID opcional | Utilizarlo para idempotencia y reintentos; `null` permitido para agentes antiguos |
| `hostname` | Cadena obligatoria | Máximo 255 caracteres |
| `ip_address` | IP obligatoria | IPv4 o IPv6 válida |
| `operating_system` | Cadena opcional | Máximo 255 caracteres |
| `cpu_usage` | Número obligatorio | Entre 0 y 100, ambos incluidos |
| `ram_usage` | Número obligatorio | Entre 0 y 100, ambos incluidos |
| `disk_usage` | Número obligatorio | Entre 0 y 100, ambos incluidos |
| `uptime_seconds` | Entero opcional | Mayor o igual a cero |

## Respuestas

| HTTP | Significado |
|---|---|
| `201 Created` | Lectura nueva almacenada |
| `200 OK` | `reading_id` previamente almacenado para ese host; no duplicar |
| `401 Unauthorized` | Falta token Bearer |
| `403 Forbidden` | Token no reconocido |
| `422 Unprocessable Content` | Campos inválidos o faltantes |
| `429 Too Many Requests` | Limitación de solicitudes por token/IP |

Ejemplo de respuesta de lectura nueva:

```json
{
  "message": "Metrics stored successfully.",
  "metric_id": 42,
  "reading_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

El identificador numérico es solo ilustrativo. La respuesta repetida informa `Metrics already stored.` y mantiene `metric_id`/`reading_id` previos.

## Semántica interna

El controlador valida el payload, resuelve el token y ejecuta una transacción. Al crear una lectura nueva actualiza hostname, IP, SO, estado del agente y `last_seen_at`, y despacha un evento de dashboard. Al repetir un UUID existente **no** modifica de nuevo `last_seen_at` ni envía una segunda notificación de métrica nueva. Un agente antiguo sin UUID puede enviar lecturas nuevas repetidas: se conserva por compatibilidad.

## Límites de tasa

`throttle:agent-metrics` utiliza límites **por token y dirección IP** definidos en `AppServiceProvider`. En el estado revisado: 10 solicitudes por minuto por token y 120 por minuto por IP. Un cliente que reciba 429 debe respetar `Retry-After` y aplicar reintentos con límites, sin cambiar `reading_id` entre intentos.

## Comprobación segura

Desde una máquina de prueba y con un token local proporcionado mediante variable de entorno (nunca compartas su contenido):

```bash
# URL local de laboratorio; el token nunca debe publicarse
curl --fail-with-body -X POST 'http://127.0.0.1:8000/api/agent/metrics' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -H "Authorization: Bearer ${AGENT_TOKEN:?Define un token local}" \
  --data '{"reading_id":"550e8400-e29b-41d4-a716-446655440000","hostname":"example-host","ip_address":"192.0.2.10","operating_system":"Linux","cpu_usage":25.5,"ram_usage":60.2,"disk_usage":70.1,"uptime_seconds":123456}'
```

En equipos remotos sustituye HTTP por HTTPS y configura conectividad segura. Un token de un host no debe utilizarse para otro host.

Pruebas relacionadas: `AgentMetricsApiTest`, `AgentMetricsBoundaryValidationTest`, `AgentMetricIdempotencyTest`, `AgentTokenSecurityTest`. Ver [seguridad](security-model.md) y [agente](agent-operations.md).
