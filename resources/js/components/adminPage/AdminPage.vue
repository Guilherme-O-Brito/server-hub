<template>
    <main class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-10 flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br from-indigo-600 to-purple-600 text-white shadow-lg"
                    aria-hidden="true"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
                        Painel administrativo
                    </h1>
                    <p class="mt-2 max-w-2xl text-base text-gray-500">
                        Gerencie usuários, versões e a infraestrutura global da plataforma.
                    </p>
                </div>
            </div>

            <ConnectedUserCard :user="auth.user" />
        </header>

        <section aria-labelledby="execution-slots-overview-title">
            <div class="mb-4">
                <h2 id="execution-slots-overview-title" class="text-xl font-bold text-gray-900">
                    Slots de execução
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Acompanhe a ocupação dos slots disponíveis.
                </p>
            </div>

            <div v-if="isLoadingSlots" class="h-44 animate-pulse rounded-2xl bg-gray-200" aria-label="Carregando slots"></div>

            <div v-else-if="executionSlots.length" class="overflow-x-auto pb-4">
                <div class="flex w-max gap-4">
                    <ExecutionSlotCard
                        v-for="slot in executionSlots"
                        :key="slot.id"
                        :slot="slot"
                    />
                </div>
            </div>

            <div v-else class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <p class="text-sm font-medium text-gray-500">Nenhum slot de execução cadastrado.</p>
            </div>
        </section>

        <div class="mt-10 space-y-6">
            <UserManagementCard />
            <MinecraftVersionsCard />
            <ExecutionSlotsCard
                :slots="executionSlots"
                :loading="isLoadingSlots"
                :error="slotsError"
                @changed="loadExecutionSlots"
            />
        </div>
    </main>
</template>

<script setup>
import { inject, onMounted, ref } from 'vue';
import ConnectedUserCard from '../ConnectedUserCard.vue';
import ExecutionSlotCard from '../servers/components/ExecutionSlotCard.vue';
import ExecutionSlotsCard from './components/ExecutionSlotsCard.vue';
import MinecraftVersionsCard from './components/MinecraftVersionsCard.vue';
import UserManagementCard from './components/UserManagementCard.vue';
import { fetchExecutionSlots } from './services/adminPageService';

const auth = inject('auth');
const executionSlots = ref([]);
const isLoadingSlots = ref(true);
const slotsError = ref('');

const loadExecutionSlots = async () => {
    isLoadingSlots.value = true;
    slotsError.value = '';

    try {
        executionSlots.value = await fetchExecutionSlots();
    } catch (error) {
        slotsError.value = error.response?.data?.message
            ?? 'Não foi possível carregar os slots de execução.';
    } finally {
        isLoadingSlots.value = false;
    }
};

onMounted(loadExecutionSlots);
</script>
