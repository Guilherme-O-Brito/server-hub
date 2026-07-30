import { createRouter, createWebHistory } from 'vue-router';
import ServersPage from '../components/servers/ServersPage.vue';
import MinecraftServerPage from '../components/minecraftServer/MinecraftServerPage.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/servidores',
            name: 'servers.index',
            component: ServersPage,
        },
        {
            path: '/servidores/minecraft/:serverId(\\d+)',
            name: 'servers.minecraft.show',
            component: MinecraftServerPage,
            props: (route) => ({
                serverId: Number(route.params.serverId),
            }),
        },
    ],
    scrollBehavior: () => ({ top: 0 }),
});

export default router;
