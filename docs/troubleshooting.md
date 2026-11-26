# Resolución de problemas frecuentes

## Composer o PHP

**`composer install` rechaza PHP 8.3:** el proyecto declara `>=8.4.1 <8.6` por dependencias de Symfony/Laravel. Utiliza la imagen de PHP 8.4 incluida, o un PHP compatible; no uses `--ignore-platform-reqs` como solución.

**`composer audit` detecta vulnerabilidades:** las advisories cambian con el tiempo. Actualiza el paquete afectado dentro de las restricciones actuales, ejecuta validación y tests; no desactives el paso para obtener un indicador verde falso.

## Docker/PostgreSQL

**`DB_HOST=127.0.0.1` falla en contenedor:** dentro de Compose debe ser `postgres`; la contraseña efectiva viene de `.env.docker` cuando la define `environment:`.

**No se levanta `app`:** comprobar `docker compose --env-file .env.docker config --quiet`, `ps`, y `logs --tail=100 app postgres vite`. Revisar credenciales antes de migrar.

**`postgres` healthy, pero Laravel falla al conectar:** comparar DB/usuario/contraseña efectiva, hostname de red Docker y migraciones. Si el volumen existía antes de cambiar `.env.docker`, cambiar la variable **no** cambia automáticamente la contraseña del usuario SQL ya creado.

**No aparece CSS/JS:** revisar contenedor Vite, puerto 5173, `npm ci`, build y `public/hot`. La plantilla usa Vite de desarrollo, no es publicación lista para Internet.

## Filament, usuarios y permisos

**Cuenta creada, pero no puede administrar usuarios:** verificar que `role=admin`; Filament `make:filament-user` no equivale necesariamente a asignar permisos del dominio.

**No aparecen botones para editar métricas:** por diseño son de solo lectura. Solo `monitor:cleanup` elimina registros de antigüedad por la vía habitual del mantenimiento.

**El último administrador no puede degradarse o borrarse:** es la protección esperada; crear/validar otra cuenta administrativa antes de traspasar responsabilidades.

## TCP, alertas y realtime

**Puerto marca offline:** comprueba dirección/puerto, firewall, servicio escuchando y conectividad desde el contenedor, no solo desde tu host. TCP accesible no garantiza salud de aplicación.

**Alertas no notifican por Telegram:** revisar `TELEGRAM_ENABLED`, token/chat por canal privado y logs de `queue`; errores de API pueden reintentarse.

**Dashboard no actualiza:** revisar `broadcast-worker`, `reverb`, WebSocket del navegador y variables `VITE_REVERB_*`, sin divulgar secretos.

## Tests

**`No module named psutil`:** estás usando Python global; `agent/.venv/bin/python -m pip install -r agent/requirements.txt` y luego ejecuta unittest con el mismo intérprete.

**`agent/venv/bin/python: No such file or directory`:** probablemente estás dentro de `backend/` o creaste `agent/.venv`. Usa ruta absoluta o vuelve a la raíz.

**Pint `ordered_imports` / `no_unused_imports`:** aplicar Pint a los archivos afectados, revisar el diff y repetir `pint --test`.

**CI falla en Composer tras pasar localmente:** una nueva advisory pudo publicarse. Compara commit, lock y actualización del índice de seguridad.

## Seguridad / recuperación

Ante un secreto publicado, rota el secreto de inmediato y evalúa exposición histórica; borrar el texto de `master` no revoca el acceso. Antes de restauraciones o migraciones destructivas, crea copia fuera del repositorio y verifica recuperación en un entorno de pruebas.
