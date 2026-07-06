import platform
import socket
import time
import requests
import psutil

API_URL = "http://127.0.0.1:8000/api/agent/metrics"
AGENT_TOKEN = "local-agent-token-123"


def get_ip_address():
    try:
        hostname = socket.gethostname()
        return socket.gethostbyname(hostname)
    except Exception:
        return "127.0.0.1"


def collect_metrics():
    return {
        "hostname": socket.gethostname(),
        "ip_address": get_ip_address(),
        "operating_system": f"{platform.system()} {platform.release()}",
        "cpu_usage": psutil.cpu_percent(interval=1),
        "ram_usage": psutil.virtual_memory().percent,
        "disk_usage": psutil.disk_usage("/").percent,
        "uptime_seconds": int(time.time() - psutil.boot_time()),
    }


def send_metrics():
    headers = {
        "Authorization": f"Bearer {AGENT_TOKEN}",
        "Accept": "application/json",
    }

    payload = collect_metrics()

    try:
        response = requests.post(API_URL, json=payload, headers=headers, timeout=5)

        if response.status_code in [200, 201]:
            print("Métricas enviadas correctamente:", payload)
        else:
            print("Error al enviar métricas:", response.status_code, response.text)

    except requests.exceptions.RequestException as error:
        print("No se pudo conectar con la API:", error)


if __name__ == "__main__":
    send_metrics()
