# Contribuir a InfraWatch

Gracias por considerar una contribución. InfraWatch busca cambios verificables, fáciles de revisar y compatibles con su arquitectura. Antes de comenzar lee [README](README.md), [índice técnico](docs/index.md), [política de seguridad](SECURITY.md) y [código de conducta](CODE_OF_CONDUCT.md).

## Qué se acepta

- Correcciones reproducibles con pruebas automatizadas.
- Documentación que corresponda al código real y comandos ejecutables.
- Mejoras de seguridad, robustez, validaciones, accesibilidad y rendimiento.
- Funcionalidades nuevas con diseño y justificación en una Issue previa si afectan arquitectura, permisos o base de datos.

No incluyas credenciales reales, datos personales, capturas con tokens ni cambios masivos sin relación con la propuesta.

## Flujo de trabajo

```bash
git clone https://github.com/Josue-Isai-Sanchez-Santos/infrawatch-monitoring-system.git
cd infrawatch-monitoring-system
git switch -c fix/descripcion-corta
# Modifica archivos, añade tests y documentación.
git status -sb
git diff --check
```

Antes del PR, comprueba lo siguiente:

```bash
cd backend
docker compose --env-file .env.docker exec app composer validate --strict
docker compose --env-file .env.docker exec app composer audit --locked --no-interaction
docker compose --env-file .env.docker exec app ./vendor/bin/pint --test
# Tests locales con SQLite en memoria; CI ejecutará PostgreSQL
docker compose --env-file .env.docker exec \
  -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: \
  -e DB_URL= -e QUEUE_CONNECTION=sync -e BROADCAST_CONNECTION=null \
  app php artisan test
```

Pruebas del agente, desde la raíz:

```bash
python3 -m venv agent/.venv
agent/.venv/bin/python -m pip install -r agent/requirements.txt
agent/.venv/bin/python -m unittest discover -s agent/tests -p 'test_*.py' -v
```

También ejecuta `npm ci`, `npm run build` y la comprobación de Compose si modificaste frontend o infraestructura.

## Reglas de diseño

1. No romper la API pública ni la idempotencia de `reading_id` sin documentar migración.
2. Mantener **separados** el estado del agente (`monitored_hosts.status`, `last_seen_at`) y la disponibilidad TCP agregada.
3. No bloquear la API con Telegram o Reverb; usar trabajos/eventos según las abstracciones actuales.
4. No añadir acciones manuales de edición/borrado al historial de métricas y chequeos.
5. Proteger al último administrador. Las operaciones sensibles necesitan autorización efectiva, no solo botones ocultos.
6. Sustituir conexiones de red por dobles de pruebas (`TcpServiceChecker`, `Http::fake`, `unittest.mock`).
7. No modificar volúmenes, credenciales o esquema PostgreSQL accidentalmente; acompañar migraciones de instrucciones para recuperación.

## Pull requests

La descripción debe incluir problema, solución, alcance, impacto, tests ejecutados, capturas si afectan UI, y cambios de documentación. Preferir PRs pequeños. Las comprobaciones de CI y seguridad deben terminar correctamente. No es garantía de fusión: el mantenedor revisará riesgos y compatibilidad.

## Licencia

Al contribuir, aceptas que tu aporte se distribuya bajo [MIT](LICENSE), la licencia del proyecto. Respeta las licencias de dependencias y recursos de terceros.
