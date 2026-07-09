# Instalación del agente InfraWatch como servicio Linux

Este documento explica cómo ejecutar el agente Python de InfraWatch como un servicio de Linux usando `systemd`.

Esto permite que el agente se inicie automáticamente con el sistema y envíe métricas de forma continua.

---

## Requisitos

- Linux con `systemd`.
- Python 3 instalado.
- Entorno virtual creado.
- Dependencias instaladas.
- Archivo `.env` configurado.
- Backend de InfraWatch accesible desde el equipo monitoreado.

---

## Ruta esperada del proyecto

Ejemplo:

```text
/home/usuario/infrawatch-monitoring-system/agent
```

Ajustar las rutas de este documento según la ubicación real del proyecto.

---

## Preparar el agente

Entrar a la carpeta del agente:

```bash
cd /home/usuario/infrawatch-monitoring-system/agent
```

Crear entorno virtual:

```bash
python3 -m venv venv
```

Activar entorno:

```bash
source venv/bin/activate
```

Instalar dependencias:

```bash
pip install -r requirements.txt
```

Crear archivo `.env`:

```bash
cp .env.example .env
```

Configurar `.env`:

```env
API_URL=http://IP_DEL_BACKEND:8000/api/agent/metrics
AGENT_TOKEN=your-agent-token-here
INTERVAL_SECONDS=60
REQUEST_TIMEOUT_SECONDS=5
RETRY_ATTEMPTS=3
RETRY_DELAY_SECONDS=3
LOG_FILE=logs/agent.log
```

Probar manualmente:

```bash
python agent.py --once --verbose
```

---

## Crear servicio systemd

Crear archivo de servicio:

```bash
sudo nano /etc/systemd/system/infrawatch-agent.service
```

Ejemplo:

```ini
[Unit]
Description=InfraWatch Python Agent
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
WorkingDirectory=/home/usuario/infrawatch-monitoring-system/agent
ExecStart=/home/usuario/infrawatch-monitoring-system/agent/venv/bin/python /home/usuario/infrawatch-monitoring-system/agent/agent.py --interval 60 --silent
Restart=always
RestartSec=10
User=usuario
Group=usuario

[Install]
WantedBy=multi-user.target
```

Cambiar estas rutas:

```text
/home/usuario/infrawatch-monitoring-system/agent
```

por la ruta real del agente.

También cambiar:

```text
User=usuario
Group=usuario
```

por el usuario real de Linux.

---

## Activar servicio

Recargar systemd:

```bash
sudo systemctl daemon-reload
```

Habilitar inicio automático:

```bash
sudo systemctl enable infrawatch-agent
```

Iniciar servicio:

```bash
sudo systemctl start infrawatch-agent
```

Ver estado:

```bash
sudo systemctl status infrawatch-agent
```

---

## Ver logs

Logs de systemd:

```bash
journalctl -u infrawatch-agent -f
```

Logs propios del agente:

```bash
tail -f logs/agent.log
```

---

## Detener servicio

```bash
sudo systemctl stop infrawatch-agent
```

---

## Reiniciar servicio

```bash
sudo systemctl restart infrawatch-agent
```

---

## Deshabilitar inicio automático

```bash
sudo systemctl disable infrawatch-agent
```

---

## Seguridad

- No subir el archivo `.env`.
- Usar tokens diferentes para cada equipo monitoreado.
- Cambiar el token si se sospecha exposición.
- Usar HTTPS para conectar con el backend en producción.
- Ejecutar el servicio con un usuario sin privilegios administrativos.
