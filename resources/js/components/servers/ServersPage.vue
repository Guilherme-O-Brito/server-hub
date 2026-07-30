<template>
    <main class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-10 flex items-start gap-4">
            <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br from-indigo-600 to-purple-600 text-white shadow-lg"
                aria-hidden="true"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z" />
                </svg>

            </div>
            <div>
                <h1 class="text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
                    Meus servidores
                </h1>
                <p class="mt-2 max-w-2xl text-base text-gray-500">
                    Visualize os servidores que você possui e aqueles que administra.
                </p>
            </div>
        </header>

        <div
            v-if="loadError"
            class="mb-8 rounded-xl border border-red-200 bg-red-200 p-4 text-sm font-medium text-red-600"
            role="alert"
        >
            Não foi possível carregar os servidores.
        </div>

        <template v-if="!isLoading">
            <section aria-labelledby="execution-slots-title">
                <div class="mb-4">
                    <h2 id="execution-slots-title" class="text-xl font-bold text-gray-900">
                        Slots de execução
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Acompanhe a ocupação dos slots disponíveis.
                    </p>
                </div>

                <div class="overflow-x-auto pb-4">
                    <div class="flex w-max gap-4">
                        <ExecutionSlotCard
                            v-for="slot in executionSlots"
                            :key="slot.id"
                            :slot="slot"
                        />
                    </div>
                </div>
            </section>

            <section class="mt-10" aria-labelledby="servers-title">
                <div class="mb-4 flex justify-between">
                    <div>
                        <h2 id="servers-title" class="text-xl font-bold text-gray-900">
                            Servidores
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Encontre um servidor e acesse seu gerenciamento.
                        </p>
                    </div>
                    <button 
                        type="button"
                        class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2"
                        @click="isCreateModalOpen = true"
                    >
                        Criar servidor
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </button>
                </div>

                <ServerFilters
                    v-model:search="searchQuery"
                    v-model:game="gameFilter"
                    v-model:access="accessFilter"
                    :games="gameOptions"
                />

                <div
                    v-if="paginatedServers.length"
                    class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <ServerCard
                        v-for="server in paginatedServers"
                        :key="server.id"
                        :server="server"
                        @manage="handleManage"
                    />
                </div>

                <div
                    v-else
                    class="mt-6 rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center"
                >
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 text-purple-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                            />
                        </svg>
                    </div>
                    <h3 class="mt-4 font-bold text-gray-900">Nenhum servidor encontrado</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Altere a busca ou os filtros para visualizar outros servidores.
                    </p>
                </div>

                <PaginationControls
                    v-if="totalPages > 1"
                    class="mt-8"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    @change="currentPage = $event"
                />
            </section>
        </template>

        <div v-else class="space-y-8" aria-label="Carregando servidores">
            <div class="h-52 animate-pulse rounded-2xl bg-gray-200"></div>
            <div class="h-96 animate-pulse rounded-2xl bg-gray-200"></div>
        </div>
    </main>

    <CreateServerModal
        :open="isCreateModalOpen"
        @close="isCreateModalOpen = false"
        @created="handleServerCreated"
    />
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import CreateServerModal from './components/CreateServerModal.vue';
import ExecutionSlotCard from './components/ExecutionSlotCard.vue';
import PaginationControls from './components/PaginationControls.vue';
import ServerCard from './components/ServerCard.vue';
import ServerFilters from './components/ServerFilters.vue';
import { fetchServerPageData } from './services/serverPageService';

const SERVERS_PER_PAGE = 8;
const router = useRouter();

const servers = ref([]);
const executionSlots = ref([]);
const searchQuery = ref('');
const gameFilter = ref('all');
const accessFilter = ref('all');
const currentPage = ref(1);
const isLoading = ref(true);
const loadError = ref(false);
const isCreateModalOpen = ref(false);

const gameOptions = computed(() => {
    const gamesByKey = new Map();

    servers.value.forEach((server) => {
        gamesByKey.set(server.game.key, server.game);
    });

    return Array.from(gamesByKey.values());
});

const filteredServers = computed(() => {
    const normalizedSearch = searchQuery.value.trim().toLocaleLowerCase('pt-BR');

    return servers.value.filter((server) => {
        const matchesSearch = server.name
            .toLocaleLowerCase('pt-BR')
            .includes(normalizedSearch);
        const matchesGame = gameFilter.value === 'all'
            || server.game.key === gameFilter.value;
        const matchesAccess = accessFilter.value === 'all'
            || server.access === accessFilter.value;

        return matchesSearch && matchesGame && matchesAccess;
    });
});

const totalPages = computed(() => Math.max(
    1,
    Math.ceil(filteredServers.value.length / SERVERS_PER_PAGE),
));

const paginatedServers = computed(() => {
    const start = (currentPage.value - 1) * SERVERS_PER_PAGE;

    return filteredServers.value.slice(start, start + SERVERS_PER_PAGE);
});

watch([searchQuery, gameFilter, accessFilter], () => {
    currentPage.value = 1;
});

const handleManage = (server) => {
    if (server.game.key === 'minecraft') {
        router.push({
            name: 'servers.minecraft.show',
            params: { serverId: server.id },
        });
    }
};

const handleServerCreated = () => {
    window.location.reload();
};

onMounted(async () => {
    try {
        const pageData = await fetchServerPageData();

        servers.value = pageData.servers;
        executionSlots.value = pageData.executionSlots;
    } catch {
        loadError.value = true;
    } finally {
        isLoading.value = false;
    }
});
</script>
