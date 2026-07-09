# Actualización en tiempo real con WebSocket

InfraWatch utiliza Laravel Reverb para enviar eventos en tiempo real al panel administrativo.

---

## Objetivo

Actualizar el dashboard cuando ocurre alguno de estos eventos:

- Llega una nueva métrica del agente.
- Se ejecuta el monitoreo TCP.
- Se crea una alerta.
- Se resuelve una alerta.
- Cambia el estado general de la infraestructura.

---

## Tecnología utilizada

- Laravel Broadcasting
- Laravel Reverb
- Laravel Echo
- Pusher JS
- Filament Admin Panel
- Vite

---

## Evento principal

```text
App\Events\DashboardUpdated
```

Canal:

```text
infrawatch.dashboard
```

Evento broadcast:

```text
dashboard.updated
```

Payload enviado:

```json
{
  "type": "host_metric_created",
  "message": "Nueva métrica recibida desde el agente.",
  "timestamp": "2026-07-08 20:00:00"
}
```

---

## Flujo general

```text
Backend detecta cambio
    ↓
Laravel dispara DashboardUpdated
    ↓
Reverb transmite evento por WebSocket
    ↓
Laravel Echo recibe evento en navegador
    ↓
Filament recarga el panel administrativo
```

---

## Archivos principales

| Archivo | Descripción |
|---|---|
| `app/Events/DashboardUpdated.php` | Evento broadcast principal. |
| `app/Http/Controllers/Api/AgentMetricController.php` | Dispara evento cuando llega una métrica. |
| `app/Console/Commands/CheckMonitoredServices.php` | Dispara evento cuando termina el chequeo TCP. |
| `app/Filament/Resources/AlertResource.php` | Dispara evento cuando se resuelve una alerta manualmente. |
| `resources/js/infrawatch-realtime.js` | Cliente WebSocket del panel. |
| `resources/js/app.js` | Entrada principal JS cargada por Vite. |
| `config/broadcasting.php` | Configuración de broadcasting. |
| `config/reverb.php` | Configuración de Laravel Reverb. |
| `routes/channels.php` | Canales de broadcasting. |

---

## Variables de entorno

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=infrawatch-local
REVERB_APP_KEY=infrawatch-local-key
REVERB_APP_SECRET=infrawatch-local-secret

REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

---

## Ejecutar en desarrollo local

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080 --debug
```

Terminal 3:

```bash
npm run dev
```

---

## Probar manualmente

Abrir Tinker:

```bash
php artisan tinker
```

Disparar evento:

```php
\App\Events\DashboardUpdated::dispatch(
    type: 'manual_test',
    message: 'Prueba manual de WebSocket.'
);
```

Resultado esperado:

```text
Reverb muestra actividad en consola.
El navegador recibe el evento.
El panel de Filament se recarga.
```

---

## Probar con flujo real

### Métricas del agente

```bash
cd agent
source venv/bin/activate
python agent.py --once
```

Resultado esperado:

```text
Se guarda una métrica.
Se dispara DashboardUpdated.
Reverb transmite el evento.
El panel administrativo se actualiza.
```

### Monitoreo TCP

```bash
cd backend
php artisan monitor:services
```

Resultado esperado:

```text
Se guardan service_checks.
Se crean o resuelven alertas si aplica.
Se dispara DashboardUpdated.
El panel administrativo se actualiza.
```

---

## Docker Compose

El servicio `reverb` puede ejecutarse desde Docker Compose:

```bash
cd backend
docker compose up -d --build
```

Servicio esperado:

```text
infrawatch_reverb
```

Ver logs:

```bash
docker compose logs -f reverb
```

---

## Consideraciones

La implementación actual recarga el panel al recibir eventos en tiempo real.

Esto es suficiente para demostrar actualización realtime en V2.0.

En una versión futura se puede mejorar para actualizar widgets específicos sin recargar toda la página.
