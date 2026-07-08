# Notificaciones por Telegram

InfraWatch puede enviar notificaciones automáticas a Telegram cuando un servicio monitoreado deja de responder o vuelve a estar disponible.

Esta integración permite recibir avisos externos sin depender únicamente del panel administrativo.

---

## Objetivo

El objetivo de esta integración es enviar mensajes automáticos cuando ocurra alguno de estos eventos:

- Un servicio TCP registrado deja de responder.
- Se genera una alerta crítica.
- Un servicio vuelve a estar disponible.
- Una alerta abierta se marca como resuelta automáticamente.

---

## Flujo general

Cuando un servicio falla:

```text
Servicio monitoreado
    ↓
monitor:services detecta fallo
    ↓
Laravel crea una alerta
    ↓
TelegramNotifier envía mensaje
    ↓
El usuario recibe aviso en Telegram
```

Cuando el servicio se recupera:

```text
Servicio vuelve online
    ↓
monitor:services detecta recuperación
    ↓
Laravel resuelve la alerta abierta
    ↓
TelegramNotifier envía mensaje de recuperación
```

---

## Archivos relacionados

| Archivo | Descripción |
|---|---|
| `app/Services/TelegramNotifier.php` | Servicio encargado de enviar mensajes a Telegram. |
| `app/Console/Commands/CheckMonitoredServices.php` | Comando que detecta fallos y llama al notificador. |
| `config/services.php` | Configuración de servicios externos, incluyendo Telegram. |
| `.env` | Variables reales de Telegram. No debe subirse al repositorio. |
| `.env.example` | Variables de ejemplo para documentar la configuración. |

---

## Crear un bot de Telegram

1. Abrir Telegram.
2. Buscar el bot oficial:

```text
@BotFather
```

3. Enviar el comando:

```text
/newbot
```

4. Seguir las instrucciones.
5. Guardar el token generado.

Ejemplo de token:

```text
1234567890:ABCDEF_xxxxxxxxxxxxxxxxxxxxx
```

Este token debe mantenerse privado.

---

## Obtener el chat ID

Primero se debe iniciar conversación con el bot en Telegram.

Pasos:

1. Abrir el bot creado.
2. Presionar `Start`.
3. Enviar un mensaje, por ejemplo:

```text
hola
```

Después, desde terminal:

```bash
TOKEN="your-telegram-bot-token"

curl "https://api.telegram.org/bot${TOKEN}/getUpdates"
```

La respuesta debe incluir una sección parecida a esta:

```json
{
  "ok": true,
  "result": [
    {
      "update_id": 123456789,
      "message": {
        "message_id": 1,
        "from": {
          "id": 123456789,
          "is_bot": false,
          "first_name": "Nombre"
        },
        "chat": {
          "id": 123456789,
          "first_name": "Nombre",
          "type": "private"
        },
        "text": "hola"
      }
    }
  ]
}
```

El valor de `chat.id` es el `TELEGRAM_CHAT_ID`.

Ejemplo:

```env
TELEGRAM_CHAT_ID=123456789
```

---

## Probar envío manual

Con el token y chat ID listos:

```bash
TOKEN="your-telegram-bot-token"
CHAT_ID="your-chat-id"

curl -X POST "https://api.telegram.org/bot${TOKEN}/sendMessage" \
  -d "chat_id=${CHAT_ID}" \
  -d "text=Prueba de InfraWatch"
```

Si todo está correcto, Telegram responderá con:

```json
{
  "ok": true
}
```

---

## Variables de entorno

En el archivo `.env` del backend:

```env
TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=your-telegram-bot-token
TELEGRAM_CHAT_ID=your-chat-id
```

En `.env.example`:

```env
TELEGRAM_ENABLED=false
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
```

Después de modificar `.env`, limpiar caché:

```bash
php artisan optimize:clear
```

---

## Configuración en Laravel

En `config/services.php` se debe agregar:

```php
'telegram' => [
    'enabled' => env('TELEGRAM_ENABLED', false),
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
],
```

---

## Servicio `TelegramNotifier`

El servicio `TelegramNotifier` centraliza el envío de mensajes.

Responsabilidades:

- Validar si Telegram está habilitado.
- Leer token y chat ID desde configuración.
- Formatear mensajes de alerta.
- Formatear mensajes de recuperación.
- Enviar peticiones HTTP a la API de Telegram.
- Registrar errores en logs si Telegram falla.

Ubicación:

```text
app/Services/TelegramNotifier.php
```

---

## Mensaje de alerta

Ejemplo de mensaje enviado cuando un servicio cae:

```text
🚨 InfraWatch Alert

Severidad: CRITICAL
Equipo: Servidor Local
Servicio: Laravel Dev Server
IP: 127.0.0.1
Puerto: 8000

Servicio caído: Laravel Dev Server

Service Laravel Dev Server is not reachable on 127.0.0.1:8000.
```

---

## Mensaje de recuperación

Ejemplo de mensaje enviado cuando un servicio vuelve a estar disponible:

```text
✅ InfraWatch Recovery

Equipo: Servidor Local
Servicio: Laravel Dev Server
IP: 127.0.0.1
Puerto: 8000

El servicio volvió a estar disponible.
```

---

## Errores comunes

### `{"ok":false,"error_code":404,"description":"Not Found"}`

La URL está mal formada o el token está mal colocado.

Formato correcto:

```bash
curl "https://api.telegram.org/bot${TOKEN}/getUpdates"
```

Errores típicos:

```text
Falta la palabra bot antes del token.
Hay un espacio entre bot y el token.
Se agregó una diagonal extra después de bot.
El token está incompleto.
```

---

### `{"ok":true,"result":[]}`

El token es válido, pero el bot no tiene mensajes pendientes.

Solución:

1. Abrir el bot en Telegram.
2. Presionar `Start`.
3. Enviar un mensaje como `hola`.
4. Ejecutar otra vez `getUpdates`.

Si sigue vacío, limpiar webhook:

```bash
curl "https://api.telegram.org/bot${TOKEN}/deleteWebhook"
```

Luego enviar otro mensaje al bot y volver a ejecutar:

```bash
curl "https://api.telegram.org/bot${TOKEN}/getUpdates"
```

---

### `chat not found`

El `TELEGRAM_CHAT_ID` está mal o el usuario todavía no ha iniciado conversación con el bot.

---

### `Unauthorized`

El `TELEGRAM_BOT_TOKEN` es incorrecto o fue copiado incompleto.

---

## Probar desde Laravel

Después de configurar `.env`, limpiar caché:

```bash
php artisan optimize:clear
```

Abrir Tinker:

```bash
php artisan tinker
```

Ejecutar:

```php
Http::post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage', [
    'chat_id' => config('services.telegram.chat_id'),
    'text' => 'Prueba de InfraWatch desde Laravel',
])->body();
```

Si responde con `"ok":true`, la integración funciona.

---

## Seguridad

- No subir `.env`.
- No publicar el token del bot.
- No compartir capturas donde aparezca el token.
- Si el token se expone, regenerarlo desde `@BotFather`.
- Usar `TELEGRAM_ENABLED=false` en entornos donde no se quiera enviar notificaciones.
- En producción, usar HTTPS para el backend y proteger las credenciales.

---

## Mejoras futuras

- Permitir múltiples chat IDs.
- Enviar notificaciones a grupos.
- Agregar niveles de severidad configurables.
- Permitir activar o desactivar notificaciones por equipo.
- Permitir activar o desactivar notificaciones por servicio.
- Agregar rate limiting para evitar spam.
- Agregar comandos desde Telegram para consultar estado.
