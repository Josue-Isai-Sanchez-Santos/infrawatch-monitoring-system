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

El agente utiliza un archivo `.env` local para cargar la URL de la API, el token del equipo monitoreado y el intervalo de ejecución.

Ejemplo de `.env`:

```env
API_URL=http://127.0.0.1:8000/api/agent/metrics
AGENT_TOKEN=your-agent-token-here
INTERVAL_SECONDS=60
```

El valor de `AGENT_TOKEN` debe coincidir con el campo `agent_token` del equipo registrado en el panel de InfraWatch.

El archivo `.env` no debe subirse al repositorio.

---

## Ejecución

### Ejecutar una sola vez

```bash
python agent.py --once
```

### Ejecutar automáticamente cada 60 segundos

```bash
python agent.py --interval 60
```

### Ejecutar usando el modo por defecto

```bash
python agent.py
```

Si no se especifica ningún argumento, el agente ejecuta un envío único.

---

## Respuesta esperada

```text
Métricas enviadas correctamente:
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

Si el token se expone, debe cambiarse desde el panel de InfraWatch y actualizarse en el `.env` local del agente.

---

## Mejoras futuras

- Instalación como servicio de sistema.
- Envío de métricas de red.
- Envío de temperatura del sistema si el hardware lo permite.
- Logs locales más detallados.
- Reintentos configurables.
- Identificador único del agente.
