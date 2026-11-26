# Historial de cambios — InfraWatch Monitoring System

Este documento registra la evolución de InfraWatch Monitoring System siguiendo las convenciones de [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y [Semantic Versioning 2.0.0](https://semver.org/lang/es/).

Las versiones V1.0 y V2.0 representan hitos históricos del desarrollo. **v2.1.0 es la primera versión formal prevista para publicación mediante GitHub Releases.**

---

## [Unreleased]

### Próximos cambios

Las funcionalidades, correcciones y actualizaciones que se incorporen después de v2.1.0 se documentarán aquí antes de asignarles una versión.

Las actualizaciones futuras seguirán estas reglas:

- **PATCH (2.1.x):** correcciones de errores, parches de seguridad y actualizaciones compatibles.
- **MINOR (2.x.0):** nuevas funcionalidades que mantengan la compatibilidad pública.
- **MAJOR (3.0.0):** cambios incompatibles en API, configuración, integraciones o contratos públicos.

---

## [2.1.0] — 2026-11-25

### Resumen

InfraWatch v2.1.0 consolida y fortalece las funcionalidades desarrolladas durante V2.0, incorporando mejoras en seguridad, administración de usuarios, monitoreo de servicios, visualización de métricas, integración continua y documentación.

Esta versión prioriza la estabilidad, la mantenibilidad del código y la protección de los datos de monitoreo.

### Añadido

- **`TcpServiceChecker`:** servicio independiente para comprobar conexiones TCP, con posibilidad de simular resultados en pruebas automatizadas.
- **`AlertResolutionService`:** servicio para resolver alertas manualmente mediante autorización y transacciones.
- Selección individual de hosts para visualizar gráficas de CPU, RAM y disco.
- Pruebas para métricas con valores límite, datos inválidos y lecturas idempotentes.
- Pruebas para hosts con múltiples servicios y recuperación de servicios caídos.
- Pruebas de la matriz de roles y Policies.
- Pruebas de resolución manual de alertas para administradores, técnicos y observadores.
- Verificación del contenido de los eventos `DashboardUpdated`.
- Pruebas del agente Python para los modos `silent` y `verbose`.
- Comprobaciones automatizadas de formato PHP mediante Laravel Pint.
- Auditorías de dependencias Composer y npm en GitHub Actions.
- Compilación del frontend mediante Vite dentro de CI.
- Validación de Docker Compose en CI.
- Escaneo de secretos mediante Gitleaks.
- Configuración de Dependabot para Composer, npm, pip, Docker y GitHub Actions.
- Plantillas para Issues y pull requests.
- Política de seguridad y documentación de contribuciones.

### Modificado

**Dashboard y métricas**

- Las gráficas de CPU, RAM y disco muestran datos correspondientes al host seleccionado.
- La selección de la última métrica utiliza `recorded_at`, en lugar de depender exclusivamente del identificador de base de datos.
- Se incorpora un criterio de desempate por identificador cuando varias métricas comparten la misma marca de tiempo.
- Se mejora la consulta de métricas recientes mediante un índice compuesto en PostgreSQL.

**Roles y administración de usuarios**

- Se deshabilita la eliminación masiva de usuarios desde Filament.
- Se impide que un administrador elimine su propia cuenta.
- Se impide modificar el propio rol desde la administración.
- Se protege al último administrador frente a eliminación o degradación.
- Se refuerza la autorización de operaciones administrativas.
- Se utiliza el cast `hashed` de Laravel para evitar procesamiento redundante de contraseñas.

**Métricas y chequeos**

- Los recursos de métricas y chequeos permanecen en modo de solo lectura desde Filament.
- Se eliminan las páginas y rutas innecesarias de creación y edición.
- Se restringen las operaciones de eliminación mediante las Policies.
- La limpieza programada del historial se mantiene mediante `monitor:cleanup`.

**Monitoreo y alertas**

- La comprobación TCP queda desacoplada del comando `monitor:services`.
- Se evita la dependencia de puertos TCP reales en las pruebas.
- Se mantiene la separación entre el estado de los servicios TCP y el estado informado por el agente Python.
- La resolución manual de alertas comprueba explícitamente los permisos del usuario.
- Se evita generar resoluciones repetidas sobre alertas que ya estaban resueltas.

**Entorno y dependencias**

- Se actualiza el requisito PHP a `>=8.4.1 <8.6`.
- Se utiliza PHP 8.4 dentro del entorno Docker.
- Se actualiza el entorno Node.js a Node 24.
- Se mantiene Laravel 13 y Filament 3 como plataformas de la línea 2.1.
- Se actualizan dependencias PHP y Python compatibles.
- Se personalizan el nombre, la descripción y las palabras clave de Composer.
- Se corrigen los parámetros PostgreSQL de `.env.example`.
- Se elimina la configuración duplicada de `BROADCAST_CONNECTION`, conservando Laravel Reverb.

### Seguridad

- Se corrigen vulnerabilidades conocidas detectadas mediante auditorías de Composer.
- Se incorpora la actualización de seguridad de `league/csv` 9.28.1.
- Se refuerza la protección de cuentas administrativas.
- Se evita la modificación manual de métricas históricas desde Filament.
- Se establece una política formal para el reporte privado de vulnerabilidades.
- Se documenta que los tokens, claves y contraseñas reales no deben publicarse en Issues, pull requests ni archivos versionados.
- Se incorporan verificaciones automáticas para detectar secretos en el repositorio.

**Nota:** superar una auditoría de dependencias no garantiza la ausencia absoluta de vulnerabilidades.

### Pruebas y calidad

Durante la validación local de esta etapa se obtuvieron:

- **82 pruebas PHP aprobadas.**
- **606 aserciones PHP correctas.**
- **15 pruebas del agente Python aprobadas.**
- Verificación satisfactoria del formato de 116 archivos PHP mediante Laravel Pint.

Estas cifras representan resultados de validación registrados durante el desarrollo. Las comprobaciones de GitHub Actions son la referencia para el estado de cada commit.

### Documentación

- Se actualiza el README principal con el estado técnico de InfraWatch.
- Se amplía la documentación del backend y del agente Python.
- Se crea `CHANGELOG.md` para el historial de versiones.
- Se crea `SECURITY.md` para políticas de seguridad.
- Se crea `CONTRIBUTING.md` para contribuciones externas.
- Se incorpora `CODE_OF_CONDUCT.md`.
- Se amplían las guías de instalación, Docker, arquitectura, API, permisos y operaciones.
- Se documentan las pruebas automatizadas y los workflows de CI.
- Se incorporan instrucciones para respaldos, recuperación y diagnóstico.
- Se establece una política de versionado y publicación de releases.

### Reconocimiento en XERJ Corpus Hub

InfraWatch Monitoring System fue incorporado a una revisión histórica de **XERJ Corpus Hub**, donde su autor figura como contribuidor.

El código indexado corresponde a una instantánea anterior a esta versión. La publicación de v2.1.0 no implica una actualización automática del corpus.

Referencia: [XERJ Corpus Hub — Perfil de contribuidor](https://hub.xerj.org/contributors/Josue-Isai-Sanchez-Santos).

---

## [V2.0] — Hito funcional histórico

Esta etapa consolidó las principales capacidades operativas de InfraWatch.

### Funcionalidades

- Backend Laravel con PostgreSQL.
- Panel administrativo desarrollado con Filament.
- Registro y administración de hosts.
- Registro y supervisión de servicios TCP.
- Comprobaciones periódicas mediante Laravel Scheduler.
- Historial de métricas y chequeos.
- Generación y resolución automática de alertas.
- Agente Python para CPU, RAM, disco y uptime.
- API REST autenticada para recepción de métricas.
- Notificaciones de fallos y recuperaciones mediante Telegram.
- Actualización del dashboard mediante Laravel Reverb y WebSockets.
- Roles de administrador, técnico y observador.
- Limpieza programada del historial de monitoreo.
- Entorno de desarrollo mediante Docker Compose.
- Primeras pruebas automatizadas y workflows de GitHub Actions.

**Estado histórico:** V2.0 identifica un hito funcional documentado. No se ha confirmado un tag o release oficial correspondiente a ese estado exacto.

---

## [V1.0] — Desarrollo inicial

Primera etapa funcional del proyecto InfraWatch Monitoring System.

### Funcionalidades iniciales

- Creación del proyecto backend Laravel.
- Integración del panel Filament.
- Configuración inicial de PostgreSQL.
- Definición del modelo de datos básico.
- Registro de equipos de infraestructura.
- Registro de servicios asociados a los equipos.
- Desarrollo de las primeras operaciones de administración.
- Preparación de la estructura para monitoreo de servicios y recepción de métricas.

**Estado histórico:** V1.0 representa una reconstrucción funcional del desarrollo inicial; no existe un release histórico certificado.

---

## Política de mantenimiento

InfraWatch utiliza el modelo `MAJOR.MINOR.PATCH`.

Las versiones publicadas mediante Git tags no se sobrescribirán posteriormente.

Las actualizaciones de dependencias propuestas por Dependabot deberán revisarse y superar las comprobaciones correspondientes antes de fusionarse.

La migración de Filament 3 a Filament 5 se evaluará como una actualización independiente. Su impacto en el número de versión dependerá de la compatibilidad de las interfaces e integraciones ofrecidas.

La política de versiones está disponible en [docs/releases.md](docs/releases.md), y el procedimiento de reporte de vulnerabilidades en [SECURITY.md](SECURITY.md).

**Publicación de v2.1.0:** este registro prepara la versión. La publicación se considerará completada cuando exista el tag `v2.1.0` y el GitHub Release correspondiente.