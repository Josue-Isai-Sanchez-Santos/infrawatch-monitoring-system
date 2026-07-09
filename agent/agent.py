import argparse
import logging
import os
import platform
import socket
import sys
import time
from pathlib import Path

import psutil
import requests
from dotenv import load_dotenv


load_dotenv()

API_URL = os.getenv("API_URL")
AGENT_TOKEN = os.getenv("AGENT_TOKEN")

DEFAULT_INTERVAL_SECONDS = int(os.getenv("INTERVAL_SECONDS", "60"))
REQUEST_TIMEOUT_SECONDS = int(os.getenv("REQUEST_TIMEOUT_SECONDS", "5"))
RETRY_ATTEMPTS = int(os.getenv("RETRY_ATTEMPTS", "3"))
RETRY_DELAY_SECONDS = int(os.getenv("RETRY_DELAY_SECONDS", "3"))
LOG_FILE = os.getenv("LOG_FILE", "logs/agent.log")


def setup_logger(verbose=False, silent=False):
    log_path = Path(LOG_FILE)
    log_path.parent.mkdir(parents=True, exist_ok=True)

    logger = logging.getLogger("infrawatch-agent")
    logger.setLevel(logging.DEBUG if verbose else logging.INFO)
    logger.handlers.clear()

    formatter = logging.Formatter(
        "%(asctime)s | %(levelname)s | %(message)s",
        datefmt="%Y-%m-%d %H:%M:%S",
    )

    file_handler = logging.FileHandler(log_path)
    file_handler.setFormatter(formatter)
    file_handler.setLevel(logging.DEBUG if verbose else logging.INFO)
    logger.addHandler(file_handler)

    if not silent:
        console_handler = logging.StreamHandler(sys.stdout)
        console_handler.setFormatter(formatter)
        console_handler.setLevel(logging.DEBUG if verbose else logging.INFO)
        logger.addHandler(console_handler)

    return logger


def get_ip_address():
    try:
        hostname = socket.gethostname()

        with socket.socket(socket.AF_INET, socket.SOCK_DGRAM) as sock:
            sock.connect(("8.8.8.8", 80))
            return sock.getsockname()[0]

    except Exception:
        try:
            return socket.gethostbyname(hostname)
        except Exception:
            return "127.0.0.1"


def collect_metrics(logger):
    logger.debug("Recolectando métricas del sistema.")

    disk_path = "/" if platform.system().lower() != "windows" else "C:\\"

    metrics = {
        "hostname": socket.gethostname(),
        "ip_address": get_ip_address(),
        "operating_system": f"{platform.system()} {platform.release()}",
        "cpu_usage": psutil.cpu_percent(interval=1),
        "ram_usage": psutil.virtual_memory().percent,
        "disk_usage": psutil.disk_usage(disk_path).percent,
        "uptime_seconds": int(time.time() - psutil.boot_time()),
    }

    logger.debug(f"Métricas recolectadas: {metrics}")

    return metrics


def validate_config():
    missing_values = []

    if not API_URL:
        missing_values.append("API_URL")

    if not AGENT_TOKEN:
        missing_values.append("AGENT_TOKEN")

    if missing_values:
        raise ValueError(
            "Faltan variables de entorno requeridas: "
            + ", ".join(missing_values)
            + ". Revisa el archivo .env."
        )


def send_metrics(logger):
    validate_config()

    headers = {
        "Authorization": f"Bearer {AGENT_TOKEN}",
        "Accept": "application/json",
        "Content-Type": "application/json",
    }

    payload = collect_metrics(logger)

    for attempt in range(1, RETRY_ATTEMPTS + 1):
        try:
            logger.info(
                f"Enviando métricas al backend. Intento {attempt}/{RETRY_ATTEMPTS}."
            )

            response = requests.post(
                API_URL,
                json=payload,
                headers=headers,
                timeout=REQUEST_TIMEOUT_SECONDS,
            )

            if response.status_code in [200, 201]:
                logger.info("Métricas enviadas correctamente.")
                logger.debug(f"Respuesta del backend: {response.text}")
                return True

            logger.warning(
                f"Error del backend. Status: {response.status_code}. "
                f"Respuesta: {response.text}"
            )

        except requests.exceptions.Timeout:
            logger.warning(
                f"Timeout al conectar con el backend. "
                f"Intento {attempt}/{RETRY_ATTEMPTS}."
            )

        except requests.exceptions.ConnectionError:
            logger.warning(
                f"No se pudo conectar con el backend. "
                f"Intento {attempt}/{RETRY_ATTEMPTS}."
            )

        except requests.exceptions.RequestException as error:
            logger.warning(
                f"Error HTTP al enviar métricas. "
                f"Intento {attempt}/{RETRY_ATTEMPTS}. Error: {error}"
            )

        if attempt < RETRY_ATTEMPTS:
            logger.info(f"Reintentando en {RETRY_DELAY_SECONDS} segundos.")
            time.sleep(RETRY_DELAY_SECONDS)

    logger.error("No se pudieron enviar las métricas después de varios intentos.")
    return False


def run_once(logger):
    logger.info("Ejecutando agente una sola vez.")
    success = send_metrics(logger)

    if success:
        logger.info("Ejecución finalizada correctamente.")
        return 0

    logger.error("Ejecución finalizada con errores.")
    return 1


def run_forever(interval_seconds, logger):
    logger.info(f"Agente iniciado en modo automático cada {interval_seconds} segundos.")

    while True:
        try:
            send_metrics(logger)
        except KeyboardInterrupt:
            logger.info("Agente detenido manualmente.")
            return 0
        except Exception as error:
            logger.exception(f"Error inesperado en ejecución continua: {error}")

        logger.debug(f"Esperando {interval_seconds} segundos.")
        time.sleep(interval_seconds)


def parse_args():
    parser = argparse.ArgumentParser(
        description="InfraWatch Python Agent",
        formatter_class=argparse.ArgumentDefaultsHelpFormatter,
    )

    execution_group = parser.add_mutually_exclusive_group()

    execution_group.add_argument(
        "--once",
        action="store_true",
        help="Ejecuta el agente una sola vez.",
    )

    execution_group.add_argument(
        "--interval",
        type=int,
        default=None,
        help="Ejecuta el agente automáticamente cada N segundos.",
    )

    parser.add_argument(
        "--verbose",
        action="store_true",
        help="Muestra logs detallados.",
    )

    parser.add_argument(
        "--silent",
        action="store_true",
        help="No muestra salida en consola. Solo escribe en archivo de log.",
    )

    return parser.parse_args()


def main():
    args = parse_args()
    logger = setup_logger(verbose=args.verbose, silent=args.silent)

    try:
        validate_config()

        if args.interval is not None:
            if args.interval <= 0:
                logger.error("El intervalo debe ser mayor a 0.")
                return 1

            return run_forever(args.interval, logger)

        if args.once:
            return run_once(logger)

        return run_once(logger)

    except KeyboardInterrupt:
        logger.info("Agente detenido manualmente.")
        return 0

    except Exception as error:
        logger.exception(f"Error crítico del agente: {error}")
        return 1


if __name__ == "__main__":
    sys.exit(main())
