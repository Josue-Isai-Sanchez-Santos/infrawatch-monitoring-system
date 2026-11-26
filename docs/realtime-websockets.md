# WebSockets y actualización del dashboard

InfraWatch usa Laravel Reverb, Laravel Echo y el evento `App\Events\DashboardUpdated`. El backend **no** mantiene la actualización en el mismo proceso HTTP: el evento se encola para evitar bloquear la operación principal.

## Contrato del evento

| Campo/método | Valor |
|---|---|
| `broadcastOn()` | Canal público `infrawatch.dashboard` |
| `broadcastAs()` | `dashboard.updated` |
| `broadcastQueue()` | `broadcasts` |
| `$connection` | `database` |
| Interface | `ShouldBroadcast`, `ShouldDispatchAfterCommit`, `ShouldRescue` |
| Payload | `type`, `message`, `metric_id`, `timestamp` |

La cola de eventos es procesada por el servicio Docker `broadcast-worker`. El servidor WebSocket corre en `reverb`; el navegador se suscribe mediante Laravel Echo configurado en `backend/resources/js/`.

## Variables

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=<ID_LOCAL>
REVERB_APP_KEY=<CLAVE_LOCAL>
REVERB_APP_SECRET=<SECRETO_LOCAL>
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

La configuración anterior es ilustrativa para un Laravel fuera de Docker. **En Compose** el backend utiliza `REVERB_HOST=reverb`; el navegador usa `VITE_REVERB_HOST=127.0.0.1` para laboratorio local. No publiques las claves/secretos y no uses HTTP/WSS inseguro en redes externas.

## Ejecutar y observar

```bash
cd backend
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs --tail=100 reverb
docker compose --env-file .env.docker logs --tail=100 broadcast-worker
```

Prueba funcional: crea una lectura de agente válida en laboratorio, comprueba la persistencia de `host_metrics`, el trabajo en cola y el evento visible en el navegador. **El canal es público:** nunca transmitas tokens, datos personales u otros secretos en el payload.

## Errores frecuentes

- Reverb activo pero panel no cambia: comprobar worker `broadcasts`, consola de navegador y variables `VITE_REVERB_*`.
- Mensajes encolados sin consumir: confirmar conexión `database`, tabla de jobs y worker en ejecución.
- Timeout al publicar desde contenedor: no usar `127.0.0.1` como destino Reverb interno, sino el servicio `reverb`.
- En producción, proxy inverso y WSS con políticas adecuadas; el Compose de desarrollo no constituye una configuración endurecida.

Pruebas relacionadas: `QueuedBroadcastTest`, `DashboardUpdatedPayloadTest`. Más información en [arquitectura](architecture.md), [seguridad](../SECURITY.md).
