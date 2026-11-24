#!/usr/bin/env python3
"""Update only the Vite base image in existing Docker Compose, preserving all other configuration."""
from pathlib import Path

compose = Path('backend/docker-compose.yml')
if not compose.exists():
    raise SystemExit('Ejecuta desde la RAÍZ de infrawatch-monitoring-system.')
source = compose.read_text(encoding='utf-8')
old = 'image: node:22-alpine'
new = 'image: node:24-alpine'
if source.count(old) == 1:
    compose.write_text(source.replace(old, new, 1), encoding='utf-8')
    print('OK: contenedor Vite actualizado a node:24-alpine.')
elif source.count(new) == 1 and old not in source:
    print('La imagen de Vite ya es node:24-alpine. No hay cambios.')
else:
    raise SystemExit('No se encontró exactamente una imagen node:22-alpine; revisa Docker Compose manualmente.')
