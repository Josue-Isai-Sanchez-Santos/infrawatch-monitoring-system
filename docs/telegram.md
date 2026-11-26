# Notificaciones por Telegram en InfraWatch

InfraWatch notifica aperturas y recuperaciones de alertas asociadas a servicios TCP. La integración es **opcional**: no se requiere una cuenta de Telegram para utilizar las demás funcionalidades.

## Componentes

- `app/Services/TelegramNotifier.php`: construcción y envío de mensajes mediante la API de Telegram.
- `app/Jobs/SendTelegramAlert.php`: job en conexión de base de datos para desacoplar el envío y permitir reintentos.
- `app/Console/Commands/CheckMonitoredServices.php`: detecta caída/recuperación y despacha un trabajo si Telegram está habilitado.

## Configuración local

```dotenv
TELEGRAM_ENABLED=false
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
```

Cuando quieras activarlo, crea un bot mediante el canal oficial de Telegram, consigue un destino válido y configura los valores **solo en tu `.env` o `.env.docker` privados**. No publiques el token ni el chat ID en GitHub Issues, screenshots o logs. La configuración Docker de `app`, `scheduler` y `queue` debe ser coherente; verifica que las variables estén disponibles en el proceso que realmente envía el job.

## Flujo

1. El monitor TCP detecta que un servicio está `offline` y crea la alerta si no existe una alerta abierta equivalente.
2. Si Telegram está habilitado, coloca un `SendTelegramAlert` en cola; no realiza una solicitud externa sincrónica en el proceso de monitor.
3. Un worker procesa el trabajo y contacta el endpoint Telegram Bot API.
4. Al recuperarse el servicio, se resuelve su alerta y se encola la notificación de recuperación.
5. Los errores de conexión o respuestas de error generan fallo/reintento conforme al job.

El trabajo tiene conexión `database`, un límite de intentos y retrasos de reintento definidos en el propio código. El comportamiento está comprobado con `Http::fake()` sin consumir la API real en tests.

## Diagnóstico

```bash
cd backend
docker compose --env-file .env.docker logs --tail=100 queue
docker compose --env-file .env.docker exec app php artisan monitor:services
```

- Bot sin chat válido: Telegram podría rechazar el mensaje; revisa la configuración en privado.
- Cola detenida: la alerta puede persistir sin que llegue la notificación.
- Token publicado: revócalo/regénéralo inmediatamente y comprueba el historial de Git.

Ver [arquitectura](architecture.md), [operación](operations.md), [SECURITY.md](../SECURITY.md) y las pruebas `TelegramQueueTest`.
