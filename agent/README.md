# InfraWatch Agent

Agente Python para InfraWatch.

Este módulo recolecta métricas básicas del sistema operativo y las envía al backend Laravel mediante una API REST.

---

## Métricas recolectadas

- Hostname
- Dirección IP
- Sistema operativo
- Uso de CPU
- Uso de RAM
- Uso de disco
- Uptime del sistema

---

## Tecnologías

| Tecnología | Uso |
|---|---|
| Python | Lenguaje del agente |
| psutil | Lectura de métricas del sistema |
| requests | Envío HTTP hacia el backend |
| python-dotenv | Lectura de variables desde `.env` |
| logging | Logs locales |
| argparse | Opciones de ejecución por terminal |

---

## Instalación

### 1. Entrar a la carpeta del agente

```bash
cd agent
```

### 2. Crear entorno virtual

```bash
python3 -m venv venv
```

### 3. Activar entorno virtual

```bash
source venv/bin/activate
```

### 4. Instalar dependencias

```bash
pip install -r requirements.txt
```

### 5. Crear archivo de configuración

```bash
cp .env.example .env
```

---

## Configuración

El agente utiliza un archivo `.env` local para cargar la URL de la API, el token del equipo monitoreado, el intervalo de ejecución, tiempos de espera, reintentos y ruta de logs.

Ejemplo de `.env`:

```env
API_URL=http://127.0.0.1:8000/api/agent/metrics
AGENT_TOKEN=your-agent-token-here
INTERVAL_SECONDS=60
REQUEST_TIMEOUT_SECONDS=5
RETRY_ATTEMPTS=3
RETRY_DELAY_SECONDS=3
LOG_FILE=logs/agent.log
```

El valor de `AGENT_TOKEN` debe coincidir con el campo `agent_token` del equipo registrado en el panel de InfraWatch.

El archivo `.env` no debe subirse al repositorio.

---

## Ejecución

### Ejecutar una sola vez

```bash
python agent.py --once
```

### Ejecutar una sola vez con logs detallados

```bash
python agent.py --once --verbose
```

### Ejecutar automáticamente cada 60 segundos

```bash
python agent.py --interval 60
```

### Ejecutar automáticamente sin salida en consola

```bash
python agent.py --interval 60 --silent
```

### Ver ayuda

```bash
python agent.py --help
```

Si no se especifica ningún argumento, el agente ejecuta un envío único.

---

## Logs locales

El agente escribe logs locales en:

```text
logs/agent.log
```

La ruta puede cambiarse desde la variable:

```env
LOG_FILE=logs/agent.log
```

Los logs no deben subirse al repositorio.

---

## Reintentos automáticos

El agente puede reintentar el envío de métricas cuando ocurre un error de red, timeout o fallo temporal del backend.

Variables relacionadas:

```env
REQUEST_TIMEOUT_SECONDS=5
RETRY_ATTEMPTS=3
RETRY_DELAY_SECONDS=3
```

---

## Respuesta esperada

```text
Métricas enviadas correctamente.
```

Si el token no existe o no coincide, el backend responderá con error de autorización.

---

## Flujo de trabajo

```text
Agente Python
    ↓
Lee configuración desde .env
    ↓
Recolecta CPU, RAM, disco y uptime
    ↓
Envía datos por HTTP POST
    ↓
Backend Laravel valida el token
    ↓
Backend guarda las métricas
    ↓
Backend dispara evento realtime
    ↓
Filament muestra los datos en Host Metrics y Dashboard
```

---

## Dependencias

Las dependencias se encuentran en:

```text
requirements.txt
```

Instalación:

```bash
pip install -r requirements.txt
```

---

## Variables de entorno

| Variable | Descripción |
|---|---|
| `API_URL` | URL del endpoint de métricas del backend. |
| `AGENT_TOKEN` | Token asignado al equipo monitoreado. |
| `INTERVAL_SECONDS` | Intervalo por defecto para ejecución automática. |
| `REQUEST_TIMEOUT_SECONDS` | Tiempo máximo de espera para la petición HTTP. |
| `RETRY_ATTEMPTS` | Número de reintentos si falla el envío. |
| `RETRY_DELAY_SECONDS` | Tiempo de espera entre reintentos. |
| `LOG_FILE` | Ruta del archivo de log local. |

---

## Instalación como servicio Linux

Para ejecutar el agente como servicio de sistema con `systemd`, revisar:

[Instalación del agente como servicio Linux](../docs/agent-service-linux.md)

---

## Seguridad

No dejar tokens reales escritos directamente en el código.

No subir el archivo:

```text
.env
```

Sí se puede subir:

```text
.env.example
```

No subir tampoco:

```text
logs/
venv/
__pycache__/
```

Si el token se expone, debe cambiarse desde el panel de InfraWatch y actualizarse en el `.env` local del agente.

---

## Mejoras futuras

- Envío de métricas de red.
- Envío de temperatura del sistema si el hardware lo permite.
- Logs locales más detallados con rotación.
- Identificador único del agente.
- Instalador automático para Linux.
