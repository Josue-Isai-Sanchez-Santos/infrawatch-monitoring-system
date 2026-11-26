# Calidad, pruebas y GitHub Actions

## Estado conocido

Una ejecución local entregada durante la revisión de noviembre de 2026 terminó con **82 tests PHP, 606 aserciones y 15 tests Python** aprobados. Es una fotografía de esa ejecución, no una garantía sobre cualquier nuevo commit. GitHub Actions puede detectar vulnerabilidades recién publicadas incluso después de una auditoría local limpia.

## Pruebas del backend

```bash
cd backend
docker compose --env-file .env.docker exec app ./vendor/bin/pint --test
docker compose --env-file .env.docker exec app composer validate --strict
docker compose --env-file .env.docker exec app composer audit --locked --no-interaction
docker compose --env-file .env.docker exec \
  -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: \
  -e DB_URL= -e QUEUE_CONNECTION=sync -e BROADCAST_CONNECTION=null \
  app php artisan test
```

Ejecuta tests siempre con una **BD de pruebas**. Las pruebas con SQLite en memoria son prácticas localmente; el workflow de backend utiliza PostgreSQL 16, que ofrece mayor confianza para migraciones y características específicas del motor.

## Pruebas del agente

```bash
# Desde la raíz del repositorio
python3 -m venv agent/.venv
agent/.venv/bin/python -m pip install -r agent/requirements.txt
agent/.venv/bin/python -m unittest discover -s agent/tests -p 'test_*.py' -v
```

Comprueban reintentos HTTP, SSL, token/red, idempotencia, SIGTERM, rotación de logs, CLI y `silent`/`verbose` sin llamadas HTTP reales.

## Tests específicos

| Archivo | Riesgo cubierto |
|---|---|
| `TcpMonitoringDeterministicTest.php` | Éxito/fallo y recuperación sin puerto real |
| `ManualAlertResolutionTest.php` | Admin/técnico/observador y resolución idempotente |
| `RolePolicyMatrixTest.php` | Permisos de recursos del panel |
| `AgentMetricsBoundaryValidationTest.php` | Rango CPU/RAM/disco y rechazo de payload inválido |
| `AgentMetricIdempotencyTest.php` | UUID/reintentos sin duplicar filas/eventos |
| `AgentTokenSecurityTest.php` | Validación de token, IP y rate limiting |
| `MonitoringHistoryReadOnlyTest.php` | Historial inmutable en Filament y limpieza de retención |
| `UserAdministrationSecurityTest.php` | Protección de administradores |
| `QueuedBroadcastTest.php`, `DashboardUpdatedPayloadTest.php` | Cola/evento de realtime |
| `TelegramQueueTest.php` | HTTP simulado, fallos y reintentos |

## Workflows

- `.github/workflows/backend-ci.yml`: PHP 8.4, Composer, Pint, auditoría de Composer, Node 24, `npm ci`, auditoría npm, compilación Vite, validación Compose, PostgreSQL, migraciones, pruebas y rutas.
- `.github/workflows/agent-ci.yml`: Python 3.12, dependencias, `compileall` y unittest. Su disparador está filtrado a cambios bajo `agent/**` y el propio workflow.
- `.github/workflows/security-gitleaks.yml`: inspección de posibles secretos en commits y PRs, además de ejecución programada.

Todos los workflows deben solicitar permisos mínimos de GitHub. El éxito de una suite no prueba por sí solo que no existan vulnerabilidades; las auditorías tampoco sustituyen tests funcionales.

## Diagnóstico

- Pint `FAIL`: ejecutar Pint sobre archivos afectos, revisar diff y repetir `--test`.
- `composer audit` falla: inspeccionar el aviso, actualizar el paquete afectado y repetir auditoría; no ocultar la vulnerabilidad.
- `psutil` ausente: utilizar el intérprete del venv.
- Gitleaks detecta un secreto real: revocar o rotar, investigar historia de Git y notificar por canal privado.
- Falla solo PostgreSQL en CI: investigar migraciones, locks, SQL no portable y transacciones.

Ver [troubleshooting](troubleshooting.md) para operaciones más amplias.
