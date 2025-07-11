import './bootstrap';

import { createApp } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy.js';
import MinecraftDashboard from './components/dashboards/minecraft/MinecraftDashboard.vue';

const dashboard = createApp();

dashboard.use(ZiggyVue, Ziggy);
dashboard.component('minecraft-dashboard', MinecraftDashboard);
dashboard.mount('#dashboard-app');


