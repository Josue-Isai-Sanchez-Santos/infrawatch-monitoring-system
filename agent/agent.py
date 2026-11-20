"""Agente de métricas InfraWatch: HTTP idempotente, logs rotativos y cierre limpio."""

import argparse
import logging
import os
import platform
import signal
import socket
import sys
import time
import uuid
from datetime import datetime, timezone
from email.utils import parsedate_to_datetime
from logging.handlers import RotatingFileHandler
from pathlib import Path

import psutil
import requests
from dotenv import load_dotenv


BASE_DIR = Path(__file__).resolve().parent
load_dotenv(BASE_DIR / ".env")

API_URL = os.getenv("API_URL")
AGENT_TOKEN = os.getenv("AGENT_TOKEN")
DEFAULT_INTERVAL_SECONDS = int(os.getenv("INTERVAL_SECONDS", "60"))
REQUEST_TIMEOUT_SECONDS = int(os.getenv("REQUEST_TIMEOUT_SECONDS", "5"))
RETRY_ATTEMPTS = int(os.getenv("RETRY_ATTEMPTS", "3"))
RETRY_DELAY_SECONDS = int(os.getenv("RETRY_DELAY_SECONDS", "3"))
LOG_FILE = os.getenv("LOG_FILE", "logs/agent.log")
LOG_MAX_BYTES = int(os.getenv("LOG_MAX_BYTES", str(5 * 1024 * 1024)))
LOG_BACKUP_COUNT = int(os.getenv("LOG_BACKUP_COUNT", "3"))

# El handler de señales solamente cambia esta bandera; no escribe logs ni
# manipula locks. El bucle principal la consulta antes de enviar o esperar.
_stop_requested = False


def request_stop(_signum, _frame):
    global _stop_requested
    _stop_requested = True


def install_signal_handlers():
    signal.signal(signal.SIGTERM, request_stop)
    signal.signal(signal.SIGINT, request_stop)


def sleep_interruptibly(seconds):
    """Espera hasta `seconds`, verificando el apagado cada 0.25 segundos."""
    deadline = time.monotonic() + max(0, seconds)
    while not _stop_requested:
        remaining = deadline - time.monotonic()
        if remaining <= 0:
            return True
        time.sleep(min(0.25, remaining))
    return False


def positive_int(value):
    number = int(value)
    if number <= 0:
        raise argparse.ArgumentTypeError("El intervalo debe ser mayor a cero.")
    return number


def validate_settings():
    if DEFAULT_INTERVAL_SECONDS <= 0:
        raise ValueError("INTERVAL_SECONDS debe ser mayor a cero.")
    if REQUEST_TIMEOUT_SECONDS <= 0:
        raise ValueError("REQUEST_TIMEOUT_SECONDS debe ser mayor a cero.")
    if RETRY_ATTEMPTS <= 0:
        raise ValueError("RETRY_ATTEMPTS debe ser mayor a cero.")
    if RETRY_DELAY_SECONDS < 0:
        raise ValueError("RETRY_DELAY_SECONDS no puede ser negativo.")
    if LOG_MAX_BYTES <= 0:
        raise ValueError("LOG_MAX_BYTES debe ser mayor a cero.")
    if LOG_BACKUP_COUNT <= 0:
        raise ValueError("LOG_BACKUP_COUNT debe ser mayor a cero.")


def setup_logger(verbose=False, silent=False):
    log_path = Path(LOG_FILE).expanduser()
    if not log_path.is_absolute():
        log_path = BASE_DIR / log_path
    log_path.parent.mkdir(parents=True, exist_ok=True)

    logger = logging.getLogger("infrawatch-agent")
    logger.setLevel(logging.DEBUG if verbose else logging.INFO)
    logger.propagate = False

    for handler in logger.handlers[:]:
        logger.removeHandler(handler)
        handler.close()

    formatter = logging.Formatter(
        "%(asctime)s | %(levelname)s | %(message)s",
        datefmt="%Y-%m-%d %H:%M:%S",
    )

    file_handler = RotatingFileHandler(
        log_path,
        maxBytes=LOG_MAX_BYTES,
        backupCount=LOG_BACKUP_COUNT,
        encoding="utf-8",
    )
    file_handler.setFormatter(formatter)
    logger.addHandler(file_handler)

    if not silent:
        console_handler = logging.StreamHandler(sys.stdout)
        console_handler.setFormatter(formatter)
        logger.addHandler(console_handler)

    return logger


def get_ip_address():
    try:
        with socket.socket(socket.AF_INET, socket.SOCK_DGRAM) as sock:
            sock.connect(("8.8.8.8", 80))
            return sock.getsockname()[0]
    except OSError:
        try:
            return socket.gethostbyname(socket.gethostname())
        except OSError:
            return "127.0.0.1"


def collect_metrics(logger):
    logger.debug("Recolectando métricas del sistema.")
    disk_path = "C:\\" if platform.system().lower() == "windows" else "/"

    metrics = {
        "reading_id": str(uuid.uuid4()),
        "hostname": socket.gethostname(),
        "ip_address": get_ip_address(),
        "operating_system": f"{platform.system()} {platform.release()}",
        "cpu_usage": psutil.cpu_percent(interval=1),
        "ram_usage": psutil.virtual_memory().percent,
        "disk_usage": psutil.disk_usage(disk_path).percent,
        "uptime_seconds": int(time.time() - psutil.boot_time()),
    }
    logger.debug("Lectura preparada: %s", metrics)
    return metrics


def validate_config():
    missing = []
    if not API_URL:
        missing.append("API_URL")
    if not AGENT_TOKEN:
        missing.append("AGENT_TOKEN")
    if missing:
        raise ValueError(
            "Faltan variables requeridas: " + ", ".join(missing)
            + ". Revisa agent/.env."
        )


def retry_after_seconds(response):
    """Retardo recomendado en HTTP 429; acepta segundos y HTTP-date."""
    header = response.headers.get("Retry-After")
    if not header:
        return RETRY_DELAY_SECONDS

    try:
        delay = float(header)
    except (ValueError, TypeError):
        try:
            deadline = parsedate_to_datetime(header)
            if deadline.tzinfo is None:
                deadline = deadline.replace(tzinfo=timezone.utc)
            delay = (deadline - datetime.now(timezone.utc)).total_seconds()
        except (ValueError, TypeError, OverflowError):
            return RETRY_DELAY_SECONDS

    # Evita esperas indefinidas causadas por un encabezado inesperado.
    if not (0 <= delay <= 300):
        return RETRY_DELAY_SECONDS if delay < 0 else 300
    return delay


def send_metrics(logger):
    validate_config()
    if _stop_requested:
        return False

    headers = {
        "Authorization": f"Bearer {AGENT_TOKEN}",
        "Accept": "application/json",
        "Content-Type": "application/json",
    }

    # Debe permanecer fuera del ciclo: mismo UUID y contenido en cada reintento.
    payload = collect_metrics(logger)

    for attempt in range(1, RETRY_ATTEMPTS + 1):
        if _stop_requested:
            return False

        logger.info("Enviando métricas. Intento %s/%s.", attempt, RETRY_ATTEMPTS)
        wait_seconds = RETRY_DELAY_SECONDS

        try:
            response = requests.post(
                API_URL,
                json=payload,
                headers=headers,
                timeout=REQUEST_TIMEOUT_SECONDS,
            )

            if response.status_code in (200, 201):
                logger.info("Métricas aceptadas (HTTP %s).", response.status_code)
                return True

            if response.status_code == 429:
                wait_seconds = retry_after_seconds(response)
                logger.warning("Límite de solicitudes (HTTP 429).")
            elif 500 <= response.status_code <= 599:
                logger.warning("Error temporal del backend (HTTP %s).", response.status_code)
            else:
                logger.error(
                    "Error HTTP no reintentable: %s. Revisar token o datos.",
                    response.status_code,
                )
                return False

        except requests.exceptions.SSLError:
            logger.error("Error de certificado TLS. No se reintentará.")
            return False
        except (requests.exceptions.Timeout, requests.exceptions.ConnectionError) as exc:
            logger.warning("Error de red: %s (intento %s).", type(exc).__name__, attempt)
        except requests.exceptions.RequestException as exc:
            logger.error("Error de solicitud no reintentable: %s.", type(exc).__name__)
            return False

        if attempt < RETRY_ATTEMPTS and not _stop_requested:
            logger.info("Reintentando en %s segundos.", wait_seconds)
            if not sleep_interruptibly(wait_seconds):
                return False

    if not _stop_requested:
        logger.error("No se pudieron enviar las métricas tras %s intentos.", RETRY_ATTEMPTS)
    return False


def run_once(logger):
    logger.info("Ejecutando agente una sola vez.")
    success = send_metrics(logger)
    if _stop_requested:
        logger.info("Agente detenido por solicitud del sistema.")
        return 0
    if success:
        logger.info("Ejecución finalizada correctamente.")
        return 0
    logger.error("Ejecución finalizada con errores.")
    return 1


def run_forever(interval_seconds, logger):
    logger.info("Agente iniciado en modo continuo: %s segundos entre ciclos.", interval_seconds)
    while not _stop_requested:
        try:
            send_metrics(logger)
        except Exception:
            logger.exception("Error inesperado en ciclo de monitoreo.")

        if _stop_requested:
            break

        logger.debug("Esperando %s segundos hasta el siguiente ciclo.", interval_seconds)
        sleep_interruptibly(interval_seconds)

    logger.info("Agente detenido correctamente.")
    return 0


def parse_args(argv=None):
    parser = argparse.ArgumentParser(
        description="InfraWatch Python Agent",
        formatter_class=argparse.ArgumentDefaultsHelpFormatter,
    )
    execution_group = parser.add_mutually_exclusive_group()
    execution_group.add_argument(
        "--once",
        action="store_true",
        help="Envía una sola lectura.",
    )
    execution_group.add_argument(
        "--interval",
        nargs="?",
        const=DEFAULT_INTERVAL_SECONDS,
        type=positive_int,
        default=None,
        metavar="SEGUNDOS",
        help="Ejecución continua: usa INTERVAL_SECONDS del .env si omites el número.",
    )
    parser.add_argument("--verbose", action="store_true", help="Logs detallados.")
    parser.add_argument("--silent", action="store_true", help="Solo registrar en archivo.")
    return parser.parse_args(argv)


def main(argv=None):
    global _stop_requested
    _stop_requested = False
    args = parse_args(argv)
    validate_settings()
    logger = setup_logger(verbose=args.verbose, silent=args.silent)
    install_signal_handlers()

    try:
        validate_config()
        if args.interval is not None:
            return run_forever(args.interval, logger)
        return run_once(logger)
    except Exception:
        logger.exception("Error crítico del agente.")
        return 1
    finally:
        for handler in logger.handlers[:]:
            handler.flush()


if __name__ == "__main__":
    sys.exit(main())
