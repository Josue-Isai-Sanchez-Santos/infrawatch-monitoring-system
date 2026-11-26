# Índice de documentación de InfraWatch

Las guías de este directorio se refieren al **estado actual de la rama `master`**, con la salvedad de que V1.0/V2.0 son hitos históricos no certificados como releases publicados. Para identificar una instalación reproduce el commit, `composer.lock`, `package-lock.json` y `agent/requirements.txt`.

## Para comenzar

| Guía | Cuándo leerla |
|---|---|
| [README principal](../README.md) | Resumen, capacidades y estado actual |
| [Instalación](installation.md) | Preparar un entorno de desarrollo sin perder datos |
| [Docker Compose](docker.md) | Comandos de contenedores, redes, puertos, volúmenes |
| [Configuración](configuration.md) | `.env`, `.env.docker`, PHP, Reverb, Telegram |
| [Operación](operations.md) | Arranque, monitoreo, retención y respaldos |
| [Diagnóstico](troubleshooting.md) | Problemas de instalación, tests, red, base de datos |

## Diseño e implementación

| Guía | Contenido |
|---|---|
| [Arquitectura](architecture.md) | Componentes, flujo de datos, dependencias |
| [Modelo de datos](data-model.md) | Entidades, relaciones, integridad y borrado |
| [API](api.md) | Endpoint de recepción de métricas |
| [Monitor TCP](tcp-monitoring.md) | Comando, `TcpServiceChecker` y alertas |
| [Agente](agent-operations.md) | Instalación, reintentos, modos y pruebas |
| [Seguridad y permisos](security-model.md) | Roles, autorización y límites |
| [Telegram](telegram.md) | Integración de notificaciones |
| [WebSocket](realtime-websockets.md) | Laravel Reverb / Laravel Echo |
| [systemd](agent-service-linux.md) | Agente Python como servicio Linux |

## Calidad y colaboración

| Guía | Contenido |
|---|---|
| [Pruebas y CI](testing-ci.md) | PHPUnit, unittest, Pint, auditorías y GitHub Actions |
| [Política de seguridad](../SECURITY.md) | Reportar fallas en privado |
| [Contribuciones](../CONTRIBUTING.md) | Flujo de PR, estilo y expectativas |
| [CHANGELOG](../CHANGELOG.md) | Hitos y cambios posteriores |
| [Versiones y releases](releases.md) | Política de etiquetas, publicación y rollback |
| [Hoja de ruta](roadmap.md) | Pendientes propuestos, no comprometidos |
| [XERJ Corpus Hub](xerj.md) | Evidencia de contribución y alcance de indexación |

## Cómo actualizar esta documentación

Cuando cambien rutas, migraciones, permisos, sintaxis de CLI o versión mayor de una dependencia, actualiza el README, la guía especializada y el CHANGELOG en el mismo PR. Evita datos de credenciales reales, capturas que expongan tokens o afirmar que un workflow pasó sin verificarlo.
