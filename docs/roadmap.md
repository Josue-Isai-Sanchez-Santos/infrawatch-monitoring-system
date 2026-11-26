# Hoja de ruta — InfraWatch

**Este documento registra propuestas, no compromisos de fecha ni funcionalidades ya disponibles.** Los puntos implementados deben moverse a [CHANGELOG](../CHANGELOG.md) junto con los tests relevantes.

## Prioridad alta — Seguridad y reproducibilidad

- Endurecer el despliegue: PHP-FPM, proxy inverso HTTPS/WSS, puertos privados, configuración sin debug, manejo de secretos y verificación de backups.
- Pruebas de concurrencia en PostgreSQL para operaciones administrativas.
- Pruebas Livewire de acciones reales de recursos Filament (incluido botón «Resolver»).
- Revisar cascadas de eliminación de hosts y servicios para una política de retención consistente.
- Matriz de compatibilidad, release notes, tags y firmas de artefactos cuando corresponda.

## Prioridad media — Observabilidad

- Comprobaciones más ricas que TCP (HTTP con validación de código y tiempos, TLS, DNS), con límites de red estrictos.
- Configuración de timeouts y concurrencia del monitor TCP.
- Alertas con deduplicación y ventanas de recuperación configurables.
- Métricas de red y temperaturas cuando el hardware permita recolectarlas.
- Reportes periódicos exportables y filtros de históricos.

## Prioridad media — Usabilidad y plataforma

- Migración **separada y probada** desde Filament 3 a versiones compatibles posteriores; evaluar Livewire 4 como dependencia de migración.
- UX de dashboard: refresco granular, estado por host y mejor accesibilidad.
- Instalación estandarizada del agente para Linux/Windows y políticas de actualización.

## Prioridad exploratoria

- Notificaciones alternativas a Telegram, multisitio/multiempresa, panel multi-tenant, despliegue en infraestructura remota y alta disponibilidad.

## Criterios para considerar una mejora lista

Debe incluir diseño de seguridad, límites y compatibilidad, implementación, test reproducible, revisión de formato, actualización de documentación y CI satisfactoria. Ninguna propuesta de esta lista habilita automáticamente cambios de infraestructura en producción.
