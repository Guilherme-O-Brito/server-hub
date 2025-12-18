import './bootstrap';

import { createApp } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy.js';
import MinecraftDashboard from './components/dashboards/minecraft/MinecraftDashboard.vue';
import AdminPage from './components/adminPage/AdminPage.vue';

const dashboard = createApp();
const adminPage = createApp(); 

dashboard.use(ZiggyVue, Ziggy);
dashboard.component('minecraft-dashboard', MinecraftDashboard);
dashboard.mount('#dashboard-app');

adminPage.use(ZiggyVue, Ziggy);
adminPage.component('admin-page', AdminPage);
adminPage.mount('#admin');

