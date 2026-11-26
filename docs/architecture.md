# Arquitectura de InfraWatch — estado de `master`

El sistema está formado por backend Laravel 13, panel Filament 3, PostgreSQL 16, agente Python, scheduler, colas persistentes y Reverb. **V2.0** es el hito funcional histórico; aquí se describen también las mejoras posteriores de `master`. No implica que exista un release etiquetado que contenga todas ellas.

## Esquema lógico

```text
       [hosts con agente Python]
                  |
               REST/TLS
                  |
        [API Laravel AgentMetricController]
                  |
         (validación/token/límites)
                  |
          [transacción PostgreSQL] ------> [host_metrics]
                  |                              |
          [evento DashboardUpdated]              |
                  |                              |
             [cola broadcasts]                   |
                  |                              |
             [servidor Reverb]                   |
                  |                              |
              [Dashboard Filament y widgets] <---+

         [Scheduler] -> [monitor:services] -> [TcpServiceChecker]
                              |
                        [service_checks]
                              |
                     [alerts/servicios]
                              |
                      [SendTelegramAlert]
                              |
                         [cola default]
                              |
                        [Telegram API]
```

## Separación de responsabilidades

- **Agente** recopila CPU/RAM/disco/uptime, incluye un UUID por lectura, reintenta transitorios y registra logs.
- **API** verifica Bearer token del host, validez de datos y límites de tasa; el UUID permite deduplicación por host.
- **Monitor TCP** comprueba puertos de servicios (no ping, no UDP). La conexión está encapsulada en `TcpServiceChecker`, sustituible en pruebas deterministas. Actualiza estado y chequeos de servicios, pero **no modifica** `monitored_hosts.status` ni `last_seen_at`, reservados al agente.
- **Alertas** se crean para servicios offline y se resuelven automáticamente al recuperarse. La resolución manual de un técnico o administrador pasa por `AlertResolutionService`, con Gate y transacción.
- **Historial** registra métricas por `recorded_at` y chequeos por `checked_at`; su limpieza de antigüedad se realiza con `monitor:cleanup` (sin borrar alertas).
- **Presentación** usa Policies para diferenciar `admin`, `technician`, `observer`; métricas/chequeos son de solo lectura en Filament; el dashboard no mezcla líneas de distintos hosts.
- **Reverb** transmite el evento público `dashboard.updated` por el canal `infrawatch.dashboard` usando una cola persistente separada.
- **Telegram** es opcional, se encola con reintentos; errores externos no deben bloquear el procesamiento principal.

## Datos y relaciones

Tablas principales: `users`, `monitored_hosts`, `monitored_services`, `host_metrics`, `service_checks`, `alerts`; además de tablas de infraestructura de Laravel como `jobs`, `cache` y `sessions`. Ver [modelo de datos](data-model.md), incluyendo cascadas de eliminación y el índice cronológico de métricas.

## Scheduler y colas

- `monitor:services`: cada minuto, `withoutOverlapping(10)`.
- `monitor:cleanup`: diariamente a las 03:00, días tomados de `config/monitoring.php`, `withoutOverlapping()`.
- Mutex compartido del scheduler en la caché **database** para evitar duplicados entre procesos.
- `queue`: cola `default`; `broadcast-worker`: cola `broadcasts`.

## Seguridad y límites

- El ejemplo Docker no está endurecido para Internet; los secretos deben configurarse fuera de Git.
- Las Policies de Filament no sustituyen controles de base de datos. La eliminación de hosts o servicios puede disparar cascadas.
- La prueba local de SQLite no equivale a probar semánticas de bloqueos de PostgreSQL; GitHub Actions usa PostgreSQL.
- El sistema no ofrece garantías de un servicio de monitorización de alta disponibilidad, ni métricas de infraestructura a nivel de red sin extensiones.

## Orientación para desarrollo

| Necesidad | Punto principal |
|---|---|
| Incorporar una métrica de agente | `agent/agent.py`, `AgentMetricController.php`, BD y tests |
| Añadir chequeo de servicio | `CheckMonitoredServices.php`, `TcpServiceChecker.php` |
| Cambiar permisos | `app/Policies`, servicios de dominio y tests |
| Cambiar gráficas | `HostMetricChartData.php`, dashboard y widgets |
| Cambiar retención | `config/monitoring.php`, `CleanupMonitoringHistory.php` |
| Cambiar realtime | `DashboardUpdated.php`, Reverb y JS |
| Notificar canales externos | Trabajo de cola y servicios existentes |

Documentación: [API](api.md), [TCP](tcp-monitoring.md), [agente](agent-operations.md), [seguridad](security-model.md), [pruebas](testing-ci.md).
