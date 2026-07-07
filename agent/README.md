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

---

## Configuración

El agente utiliza un archivo `.env` local para cargar la URL de la API y el token del equipo monitoreado.

Crear el archivo `.env`:

```bash
cp .env.example .env
```

Ejemplo de `.env`:

```env
API_URL=http://127.0.0.1:8000/api/agent/metrics
AGENT_TOKEN=your-agent-token-here
```

El valor de `AGENT_TOKEN` debe coincidir con el campo `agent_token` del equipo registrado en el panel de InfraWatch.

El archivo `.env` no debe subirse al repositorio.

---

## Ejecución

Con el backend encendido:

```bash
python agent.py
```

Respuesta esperada:

```text
Métricas enviadas correctamente
```

Si el token no existe o no coincide, el backend responderá con error de autorización.

---

## Flujo de trabajo

```text
Agente Python
    ↓
Recolecta CPU, RAM, disco y uptime
    ↓
Envía datos por HTTP POST
    ↓
Backend Laravel valida el token
    ↓
Backend guarda las métricas
    ↓
Filament muestra los datos en Host Metrics
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

## Seguridad

No se recomienda dejar tokens reales escritos directamente en el código en entornos productivos.

Para una versión más segura se recomienda usar variables de entorno o un archivo `.env` local no rastreado por Git.

El archivo `.env` debe permanecer fuera del repositorio.

---

## Mejoras futuras

- Lectura de configuración desde `.env`.
- Ejecución continua cada cierto intervalo.
- Instalación como servicio de sistema.
- Envío de métricas de red.
- Envío de temperatura del sistema si el hardware lo permite.
- Logs locales del agente.
