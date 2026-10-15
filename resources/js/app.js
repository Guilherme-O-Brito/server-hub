import './bootstrap';

import { createApp, reactive } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import axios from 'axios';
import App from './components/App.vue';
import AdminPage from './components/adminPage/AdminPage.vue';
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

const configElement = document.getElementById('app-config');

if (!configElement) {
    throw new Error('Elemento #app-config não encontrado.');
}

let authUser = null;

try {
    const appConfig = JSON.parse(configElement.textContent);
    authUser = appConfig.authUser ?? null;
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

const adminAppElement = document.getElementById('admin-app');

if (adminAppElement) {
    createApp(AdminPage)
        .use(ZiggyVue)
        .provide('auth', auth)
        .mount(adminAppElement);
}
