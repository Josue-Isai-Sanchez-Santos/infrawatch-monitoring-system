# Monitoreo de servicios TCP

## Objetivo y alcance

El comando `php artisan monitor:services` comprueba la posibilidad de abrir una **conexión TCP** al puerto registrado. No hace análisis profundo de protocolo, comprobación de contenido HTTP, ping ICMP ni pruebas UDP. El estado "online" significa que se pudo establecer la conexión, no que la aplicación esté completamente sana.

## Implementación

- `app/Console/Commands/CheckMonitoredServices.php`: obtiene servicios con host, omite protocolos diferentes de TCP, guarda resultados y gestiona alertas.
- `app/Services/TcpServiceChecker.php`: encapsula `fsockopen($ip, $port, ..., 3)`; devuelve `reachable`, `response_time_ms`, `error`.
- `app/Models/ServiceCheck.php`: historial por servicio.
- `app/Models/Alert.php`: estado `open`/`resolved` de alertas asociadas.
- `app/Services/AlertResolutionService.php`: resolución **manual** mediante Policy y transacción (camino distinto del monitor automático).

## Flujo

1. El scheduler ejecuta `monitor:services` cada minuto con exclusión de solapamiento.
2. Se cargan los servicios; un protocolo no TCP se omite sin generar un chequeo falso.
3. El chequeador TCP devuelve éxito/fracaso y duración.
4. Se agrega un `service_check`, se actualiza el estado del servicio y `last_checked_at`.
5. Si falla, se mantiene/crea una alerta abierta por servicio y tipo.
6. Si se recupera, se resuelven alertas abiertas correspondientes a **ese servicio**.
7. Según configuración, se encolan notificaciones de Telegram; se emite `DashboardUpdated`.

**Independencia:** el monitor TCP no sobrescribe `monitored_hosts.status` ni `last_seen_at`, que representan la comunicación del agente.

## Pruebas deterministas

El monitor recibe `TcpServiceChecker` por el contenedor de Laravel. Los tests lo sustituyen por un doble predecible para simular puertos abiertos/cerrados, variación de tiempos, recuperaciones y múltiples servicios en un mismo host. Esto evita depender del puerto 1 de `127.0.0.1` o de procesos externos.

Pruebas representativas: `TcpMonitoringDeterministicTest`, `HostStatusSeparationTest`, `MonitorServicesCommandTest`, `UnsupportedProtocolTest`, `TelegramQueueTest`.

## Operación y límites

```bash
cd backend
docker compose --env-file .env.docker exec app php artisan monitor:services
```

El timeout TCP en el código es de 3 segundos por conexión. Un gran número de servicios podría prolongar el ciclo y requerir paralelización, timeouts configurables y controles de concurrencia; esas mejoras no se dan por implementadas.
