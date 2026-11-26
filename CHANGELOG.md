# Historial de cambios — InfraWatch

Este archivo sigue las ideas de [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y [Semantic Versioning](https://semver.org/lang/es/). **No se inventan fechas, tags ni releases**: las secciones V1.0 y V2.0 documentan hitos funcionales; confirmar el commit histórico antes de publicar una etiqueta formal.

## [Unreleased] — cambios posteriores a V2.0 presentes en `master`

### Añadido
- Pruebas de integración para validación de métricas, resolución manual de alertas y matriz de roles.
- `TcpServiceChecker` reemplazable en las pruebas para evitar dependencias de puertos abiertos o cerrados.
- `AlertResolutionService` para resolver alertas mediante autorización y operación transaccional.
- Tests Python de logging `silent` y `verbose`, reintentos, interrupción limpia e idempotencia.
- Pruebas de configuración de `.env.example`, seguridad administrativa y retención de historial.
- Flujos separados de GitHub Actions para backend, agente y escaneo Gitleaks; auditorías y compilación frontend.

### Modificado
- Gráficas CPU/RAM/disco por host, con series separadas y selección de equipo.
- Última métrica por host basada en `recorded_at` y desempate por identificador, no en `MAX(id)`.
- Policies de métricas y chequeos de solo lectura desde Filament; limpieza mediante `monitor:cleanup`.
- Administración de usuarios: eliminación masiva inhabilitada, protección de cuenta propia y del último administrador.
- Requisito PHP `>=8.4.1 <8.6`; imágenes de PHP 8.4 y Node 24; actualizaciones de Laravel 13 y bibliotecas vulnerables.
- Metadatos del paquete Composer y configuración de PostgreSQL/Reverb en `.env.example`.

### Seguridad
- Actualizaciones de dependencias que resuelven los avisos detectados durante las auditorías de noviembre de 2026, incluido `league/csv` 9.28.1.
- Nota: una auditoría limpia significa *sin avisos conocidos en ese momento*, no ausencia absoluta de vulnerabilidades.

## [V2.0] — hito funcional documentado, **release formal no confirmado**

- Dashboard administrativo Filament con gráficos, estadísticas, tablas de métricas, alertas y servicios.
- Backend Laravel y PostgreSQL; despliegue para desarrollo mediante Docker Compose.
- Agente Python para CPU, RAM, disco y uptime con envío HTTP autenticado.
- Chequeos TCP con Laravel Scheduler, historial de chequeos y alertas automáticas.
- Notificaciones por Telegram, eventos mediante Laravel Reverb y actualización del dashboard.
- Usuarios con roles administrativos, técnicos y de observación; limpieza programada de historial.
- Pruebas automatizadas y GitHub Actions como base de calidad.

## [V1.0] — etapa inicial, **reconstrucción funcional**

La documentación inicial no determina un tag, fecha o commit exacto para V1.0. Se presenta como etapa de creación del núcleo del proyecto, no como certificación de un artefacto publicado.

- Primera estructura de backend Laravel, Filament y PostgreSQL.
- Modelos de hosts y servicios y operaciones básicas de inventario.
- Esqueleto de recepción de métricas y supervisión de servicios.

## Criterios de futuras versiones

| Escenario | Acción recomendada |
|---|---|
| Corregir errores y vulnerabilidades sin cambiar funcionalidades | Incremento `PATCH` |
| Agregar funciones compatibles, pruebas y mejoras operativas | Incremento `MINOR` |
| Romper compatibilidad de API, configuración o datos | Incremento `MAJOR` |
| Migrar Filament 3 a 5 o modificar el modelo de datos | Rama y plan de migración; valorar impacto sobre versión |

**Pendiente de formalización:** localizar el commit exacto que represente V2.0 y comprobar los tags existentes antes de publicar `v2.0.0`; una etiqueta creada hoy sobre `master` también incluiría mejoras posteriores. Ver [guía de releases](docs/releases.md).
