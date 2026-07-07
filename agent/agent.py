import os
import platform
import socket
import time

import psutil
import requests
from dotenv import load_dotenv


load_dotenv()

API_URL = os.getenv("API_URL")
AGENT_TOKEN = os.getenv("AGENT_TOKEN")


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


def validate_config():
    if not API_URL:
        raise ValueError("Missing API_URL. Please configure it in the .env file.")

    if not AGENT_TOKEN:
        raise ValueError("Missing AGENT_TOKEN. Please configure it in the .env file.")


def send_metrics():
    validate_config()

    headers = {
        "Authorization": f"Bearer {AGENT_TOKEN}",
        "Accept": "application/json",
    }

    payload = collect_metrics()

    try:
        response = requests.post(API_URL, json=payload, headers=headers, timeout=5)

        if response.status_code in [200, 201]:
            print("Métricas enviadas correctamente:")
            print(payload)
            return True

        print("Error al enviar métricas:")
        print("Status code:", response.status_code)
        print("Response:", response.text)
        return False

    except requests.exceptions.RequestException as error:
        print("No se pudo conectar con la API:")
        print(error)
        return False


if __name__ == "__main__":
    send_metrics()
