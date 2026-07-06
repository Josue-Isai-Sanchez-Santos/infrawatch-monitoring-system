# InfraWatch Agent

Agente Python para InfraWatch.

Este módulo recolecta métricas básicas del sistema operativo y las envía al backend Laravel mediante una API REST.

---

## Métricas recolectadas

El agente recolecta:

- Hostname
- Dirección IP
- Sistema operativo
- Uso de CPU
- Uso de RAM
- Uso de disco
- Uptime del sistema

---

## Tecnologías

- Python
- psutil
- requests

---

## Instalación

Entrar a la carpeta del agente:

```bash
cd agent

Crear entorno virtual:

python3 -m venv venv

Activar entorno virtual:

source venv/bin/activate

Instalar dependencias:

pip install -r requirements.txt
Configuración

El agente necesita conocer la URL de la API y el token asignado al equipo monitoreado.

Ejemplo de configuración dentro de agent.py:

API_URL = "http://127.0.0.1:8000/api/agent/metrics"
AGENT_TOKEN = "your-agent-token"

El token debe coincidir con el campo agent_token del equipo registrado en el panel de InfraWatch.

Ejecución

Con el backend encendido:

python agent.py

Respuesta esperada:

Métricas enviadas correctamente

Si el token no existe o no coincide, el backend responderá con error de autorización.

Flujo de trabajo
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
Dependencias

Las dependencias se encuentran en:

requirements.txt

Instalación:

pip install -r requirements.txt
Seguridad

No se recomienda dejar tokens reales escritos directamente en el código en entornos productivos.

Para una versión más segura se recomienda usar variables de entorno o un archivo .env local no rastreado por Git.

El archivo .env debe permanecer fuera del repositorio.

Mejoras futuras
Lectura de configuración desde .env.
Ejecución continua cada cierto intervalo.
Instalación como servicio de sistema.
Envío de métricas de red.
Envío de temperatura del sistema si el hardware lo permite.
Logs locales del agente.
