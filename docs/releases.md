# Versionado, tags y releases de InfraWatch

## Principio

Un tag es una referencia **inmutable por convención** a un commit concreto; un GitHub Release agrega descripción y artefactos a una etiqueta. No corresponde marcar una versión histórica como si el código actual fuera el código de entonces.

## Estado de la numeración

- **V1.0:** etapa funcional inicial descrita retrospectivamente en el changelog. No se ha confirmado un commit o release histórico.
- **V2.0:** hito funcional del README original con agentes, alertas, Docker y realtime. **No se ha certificado un commit de corte**.
- **`master`:** tiene correcciones posteriores a V2.0 (seguridad, mejoras de permisos, refactor TCP, tests y CI). Se recomienda considerar una futura versión `v2.1.0` si el mantenedor decide formalizar este conjunto como lanzamiento compatible.

El nombre de versión definitiva debe elegirse tras comprobar el estado de los tags y CI; no se crea ninguno automáticamente con este paquete.

## Identificar el commit histórico de V2.0

Desde la raíz del clon:

```bash
git fetch origin --tags
git tag --list 'v*' --sort=version:refname
git log --oneline --decorate --all --max-count=80
git log --oneline -- README.md
```

Busca un commit **anterior a los cambios posteriores a V2.0**, con todas las características anunciadas en ese hito, y verifica su árbol. Nunca selecciones un commit únicamente por su fecha o por la etiqueta que desees crear. Si no puede determinarse con evidencia, no inventes el tag.

### Si se identifica el commit exacto de V2.0

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
