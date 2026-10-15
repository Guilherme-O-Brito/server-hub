import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const appConfigElement = document.getElementById('app-config');

if (!appConfigElement) {
    throw new Error('Elemento #app-config não encontrado.');
}

const { reverb } = JSON.parse(appConfigElement.textContent);

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: reverb.key,
    wsHost: reverb.host,
    wsPort: Number(reverb.port),
    wssPort: Number(reverb.port),
    forceTLS: reverb.scheme === 'https',
    enabledTransports: ['ws', 'wss'],
});
