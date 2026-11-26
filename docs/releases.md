# Versionado, tags y releases de InfraWatch

## Principio

Un tag es una referencia **inmutable por convención** a un commit concreto; un GitHub Release agrega descripción y artefactos a una etiqueta. No corresponde marcar una versión histórica como si el código actual fuera el código de entonces.

## Estado de la numeración

InfraWatch adopta **Semantic Versioning 2.0.0** como política de versionado.

### Historial del proyecto

- **V1.0:** primera etapa funcional del sistema, con Laravel, Filament, PostgreSQL y los modelos básicos de infraestructura. No se ha identificado un release oficial histórico.
- **V2.0:** consolidación del monitoreo TCP, agente Python, dashboard, Telegram, roles, Docker Compose y actualizaciones mediante WebSocket. Se considera un hito funcional documentado, no un release histórico certificado.
- **v2.1.0:** primera publicación formal prevista de la línea 2.1. Consolida correcciones de seguridad, protección de administradores, mejoras de historial, pruebas deterministas, auditorías de dependencias, integración continua y documentación técnica.

La publicación de `v2.1.0` solo se considera completada cuando existe una etiqueta Git válida y el correspondiente release ha sido publicado en GitHub.

### Política de versiones futuras

| Tipo | Criterio | Ejemplo |
|---|---|---|
| PATCH | Correcciones de errores, vulnerabilidades y actualizaciones compatibles | `2.1.0` → `2.1.1` |
| MINOR | Nuevas funcionalidades que conservan compatibilidad | `2.1.0` → `2.2.0` |
| MAJOR | Cambios incompatibles en API, configuración, contratos o integraciones | `2.x` → `3.0.0` |

Las actualizaciones propuestas por Dependabot no se fusionarán automáticamente. Cada cambio deberá superar las verificaciones correspondientes y ser revisado antes de incorporarse a `master`.

Una migración interna importante, como Filament 3 a Filament 5, no obliga por sí sola a incrementar MAJOR; la decisión dependerá de si rompe compatibilidad pública.

### Mantenimiento

- `master` representa la línea principal de desarrollo.
- Los tags `vX.Y.Z` identifican versiones publicadas y no deben modificarse después de su publicación.
- Las correcciones de seguridad se priorizarán según gravedad, exposición y compatibilidad.
- No se garantiza mantenimiento indefinido de versiones antiguas.
- Las vulnerabilidades deberán reportarse conforme a [SECURITY.md](../SECURITY.md).

El historial detallado de modificaciones se encuentra en [CHANGELOG.md](../CHANGELOG.md).

## Identificar el commit histórico de V2.0

Desde la raíz del clon:

```bash
git fetch origin --tags
git tag --list 'v*' --sort=version:refname
git log --oneline --decorate --all --max-count=80
git log --oneline -- README.md
```

Busca un commit **anterior a los cambios posteriores a V2.0**, con todas las características anunciadas en ese hito, y verifica su árbol. Nunca selecciones un commit únicamente por su fecha o por la etiqueta que desees crear. Si no puede determinarse con evidencia, no inventes el tag.

### Publicación de InfraWatch v2.1.0

La versión `v2.1.0` deberá etiquetar exclusivamente el commit de `master` que haya superado las verificaciones de integración continua y contenga el historial de cambios correspondiente.

Antes de crear el tag:

1. Fusionar el pull request de preparación del release.
2. Comprobar que Backend CI, Python Agent CI y Gitleaks hayan terminado correctamente.
3. Verificar que `composer audit --locked` y las demás auditorías no presenten problemas de seguridad pendientes de evaluación.
4. Confirmar que `CHANGELOG.md` contiene la sección `2.1.0`.
5. Sincronizar `master` y comprobar que el árbol de trabajo esté limpio.
6. Crear una etiqueta anotada `v2.1.0` sobre el commit aprobado y publicarla en GitHub.

No deben agregarse commits funcionales a la versión después de crear el tag. Las correcciones posteriores deberán publicarse como `v2.1.1`, `v2.1.2` o la versión que corresponda según la política establecida.

```bash
# Reemplazar la variable con el SHA histórico realmente verificado:
COMMIT_V2="<SHA-HISTORICO-VERIFICADO>"
# Inspeccionar antes de crear la etiqueta:
git show --stat "$COMMIT_V2"
git show "$COMMIT_V2:README.md" | head -50
# Confirmar que el tag aún no existe:
git rev-parse --verify refs/tags/v2.0.0
# Si devuelve que NO existe, recién entonces:
git tag -a v2.0.0 "$COMMIT_V2" -m "InfraWatch Monitoring System V2.0"
git show v2.0.0 --no-patch
git push origin v2.0.0
```

**No ejecutes literalmente el bloque anterior sin sustituir `<SHA-HISTORICO-VERIFICADO>` y comprobar el resultado de `git rev-parse`.** Un fallo en esa verificación es el requisito para crear un tag nuevo, no para sobrescribir uno existente.

### Si decides lanzar el `master` actual como nueva versión

Después de confirmar Backend CI, Agent CI (si aplica) y Gitleaks, documentar cambios y verificar dependencias:

```bash
git switch master
git pull --ff-only origin master
git status -sb
git tag --list 'v2.1.0'
# Solo si no existe y el mantenedor decide formalizar v2.1.0:
git tag -a v2.1.0 -m "InfraWatch Monitoring System V2.1.0"
git push origin v2.1.0
```

No llames `v2.0.0` a un `master` posterior al hito histórico. Si se adopta otra numeración, ajustar CHANGELOG y release notes de forma coherente.

## Publicar GitHub Release

1. Abrir [Releases](https://github.com/Josue-Isai-Sanchez-Santos/infrawatch-monitoring-system/releases).
2. Seleccionar `Draft a new release` y elegir un **tag ya verificado**.
3. Documentar cambios principales, compatibilidad PHP/BD, pruebas y problemas conocidos.
4. Revisar el preview, referencias y artefactos antes de publicar.
5. No anunciar seguridad perfecta ni uptime garantizado.

## Lista de verificación

- [ ] La etiqueta corresponde al código que se describe.
- [ ] Todas las pruebas CI relevantes pasaron en ese SHA.
- [ ] `composer audit --locked` y `npm audit` no muestran avisos sin evaluar.
- [ ] No hay secretos en commits, archivos ni capturas.
- [ ] Están actualizados README, CHANGELOG, SECURITY y documentación de upgrade.
- [ ] La BD tiene plan de backup/restore y migraciones revisadas.
- [ ] Se enumeran limitaciones conocidas y diferencias de versiones.

Referencias: [GitHub Releases](https://docs.github.com/en/repositories/releasing-projects-on-github/about-releases) y [Semantic Versioning](https://semver.org/lang/es/).
