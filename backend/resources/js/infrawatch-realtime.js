import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/*
|--------------------------------------------------------------------------
| Configuración de Laravel Reverb
|--------------------------------------------------------------------------
*/

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

const reverbHost =
    import.meta.env.VITE_REVERB_HOST ??
    window.location.hostname;

const reverbPort =
    import.meta.env.VITE_REVERB_PORT ?? 8080;

const reverbScheme =
    import.meta.env.VITE_REVERB_SCHEME ?? 'http';

/*
|--------------------------------------------------------------------------
| Rutas permitidas para la recarga automática
|--------------------------------------------------------------------------
|
| Solamente el dashboard principal de InfraWatch
| puede recargarse automáticamente.
|
| No se recargan formularios, páginas de edición,
| listas de recursos ni páginas administrativas.
|
*/

const dashboardPaths = [
    '/admin',
    '/admin/',
];

const DASHBOARD_RELOAD_DELAY = 800;

// Controla si ya hay una recarga programada.
let dashboardReloadTimer = null;

/**
 * Comprueba si la página actual es el dashboard.
 *
 * No utilizamos includes('/admin'), startsWith()
 * ni expresiones que permitan rutas secundarias.
 */
function isDashboardPage() {
    return dashboardPaths.includes(
        window.location.pathname
    );
}

/**
 * Programa una única recarga del dashboard.
 *
 * Si el usuario navega a otra página antes de
 * que termine el temporizador, no se recarga.
 */
function scheduleDashboardReload() {
    // Nunca recargar una página diferente
    // del dashboard principal.
    if (!isDashboardPage()) {
        return;
    }

    // Evitar múltiples temporizadores cuando
    // llegan eventos consecutivos.
    if (dashboardReloadTimer !== null) {
        return;
    }

    dashboardReloadTimer = window.setTimeout(() => {
        dashboardReloadTimer = null;

        // Segunda comprobación de seguridad:
        // el usuario podría haber navegado
        // a un formulario durante la espera.
        if (!isDashboardPage()) {
            return;
        }

        window.location.reload();
    }, DASHBOARD_RELOAD_DELAY);
}

/*
|--------------------------------------------------------------------------
| Conexión WebSocket
|--------------------------------------------------------------------------
*/

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    /*
    |--------------------------------------------------------------------------
    | Eventos de InfraWatch
    |--------------------------------------------------------------------------
    */

    window.Echo
        .channel('infrawatch.dashboard')
        .listen('.dashboard.updated', (event) => {
            console.log(
                'InfraWatch realtime update:',
                event
            );

            /*
             * Conservamos el evento global.
             *
             * Otros componentes pueden escucharlo
             * sin depender de una recarga completa.
             */
            window.dispatchEvent(
                new CustomEvent(
                    'infrawatch-dashboard-updated',
                    {
                        detail: event,
                    }
                )
            );

            /*
             * Solo el dashboard principal puede
             * programar una recarga automática.
             */
            scheduleDashboardReload();
        });
}
