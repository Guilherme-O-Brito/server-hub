import './bootstrap';

import { createApp } from 'vue';
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

const serversAppElement = document.getElementById('servers-app');

if (serversAppElement) {
    createApp(App)
        .use(ZiggyVue)
        .use(router)
        .mount(serversAppElement);
}
