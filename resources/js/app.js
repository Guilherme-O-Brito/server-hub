import './bootstrap';

import { createApp, reactive } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import axios from 'axios';
import App from './components/App.vue';
import router from './router';

axios.defaults.withCredentials = true;
axios.defaults.headers.common.Accept = 'application/json';

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content');

if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

window.axios = axios;

const rootElement = document.getElementById('authUser');

if (!rootElement) {
    throw new Error('Elemento #authUser não encontrado.');
}

let authUser = null;

try {
    authUser = rootElement.dataset.authUser
        ? JSON.parse(rootElement.dataset.authUser)
        : null;
} catch (error) {
    console.error('Não foi possível carregar o usuário autenticado.', error)
}

const auth = reactive({
    user: authUser,
});

const serversAppElement = document.getElementById('servers-app');

if (serversAppElement) {
    createApp(App)
        .use(ZiggyVue)
        .use(router)
        .provide('auth', auth)
        .mount(serversAppElement);
}
