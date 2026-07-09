import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const reverbPort = import.meta.env.VITE_REVERB_PORT ?? 8080;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';

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

    window.Echo
        .channel('infrawatch.dashboard')
        .listen('.dashboard.updated', (event) => {
            console.log('InfraWatch realtime update:', event);

            window.dispatchEvent(
                new CustomEvent('infrawatch-dashboard-updated', {
                    detail: event,
                })
            );

            const currentPath = window.location.pathname;

            if (currentPath.includes('/admin')) {
                setTimeout(() => {
                    window.location.reload();
                }, 800);
            }
        });
}
