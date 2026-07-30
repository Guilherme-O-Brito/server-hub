<template>
    <main class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div v-if="isLoading" class="space-y-6" aria-label="Carregando servidor">
            <div class="h-24 animate-pulse rounded-2xl bg-gray-200"></div>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="h-48 animate-pulse rounded-2xl bg-gray-200"></div>
                <div class="h-48 animate-pulse rounded-2xl bg-gray-200"></div>
                <div class="h-48 animate-pulse rounded-2xl bg-gray-200"></div>
            </div>
            <div class="h-96 animate-pulse rounded-2xl bg-gray-200"></div>
        </div>

        <section
            v-else-if="pageError"
            class="rounded-2xl border border-red-200 bg-white px-6 py-16 text-center shadow-sm"
        >
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-200 text-red-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.7 2.7 17a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z" />
                </svg>
            </div>
            <h1 class="mt-4 text-xl font-black text-gray-900">{{ pageError.title }}</h1>
            <p class="mt-2 text-sm text-gray-500">{{ pageError.message }}</p>
        </section>

        <template v-else-if="server">
            <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-purple-100 p-2">
                        <img
                            :src="'/imgs/icons/minecraft.png'"
                            alt="Ícone do Minecraft"
                            class="h-full w-full object-contain"
                        />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
                                {{ server.name }}
                            </h1>
                            <StatusBadge :status="server.status ?? 'unknown'" />
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Minecraft
                            <span v-if="server.version?.version">
                                · versão {{ server.version.version }}
                            </span>
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2"
                    @click="isSettingsOpen = true"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Configurações
                </button>
            </header>

            <section class="grid grid-cols-1 gap-5 lg:grid-cols-3" aria-label="Resumo do servidor">
                <PlayersSummaryCard :players="server.players" />
                <ResourcesSummaryCard :resources="server.resources" />
                <ServerControlsCard
                    :status="server.status"
                    :busy="isControlBusy"
                    :error="controlError"
                    @start="handleStart"
                    @stop="handleStop"
                />
            </section>

            <section class="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-2" aria-label="Permissões do servidor">
                <AccessListCard
                    title="Whitelist"
                    description="Nicknames autorizados a entrar no servidor."
                    :entries="whitelist"
                    :can-add="server.status === 'stopped'"
                    :can-delete="server.status === 'stopped'"
                    :loading="isWhitelistLoading"
                    :error="whitelistError"
                    @add="openNicknameModal('whitelist')"
                    @delete-entry="openAccessDeletion('whitelist', $event)"
                />
                <AccessListCard
                    title="Operators"
                    description="Nicknames com permissões de operator."
                    :entries="operators"
                    :can-add="server.status === 'stopped'"
                    :can-delete="server.status === 'stopped'"
                    :loading="isOperatorsLoading"
                    :error="operatorsError"
                    @add="openNicknameModal('operators')"
                    @delete-entry="openAccessDeletion('operators', $event)"
                />
            </section>

            <ServerConsole class="mt-8" :status="server.status" />
        </template>
    </main>

    <ServerSettingsModal
        :open="isSettingsOpen"
        :server="server"
        @close="isSettingsOpen = false"
        @updated="handleSettingsUpdated"
        @request-delete="openServerDeletion"
    />

    <NicknameModal
        :open="Boolean(nicknameTarget)"
        :target="nicknameTarget ?? 'whitelist'"
        :submitting="isAddingNickname"
        :error="nicknameError"
        @close="closeNicknameModal"
        @submit="handleNicknameSubmit"
    />

    <DeleteConfirmationModal
        :open="Boolean(deletionTarget)"
        :resource-label="deletionTarget?.resourceLabel ?? 'recurso'"
        :resource-name="deletionTarget?.resourceName ?? ''"
        :detail="deletionTarget?.detail ?? ''"
        :submitting="isDeleting"
        :error="deletionError"
        @close="closeDeletionModal"
        @confirm="handleDelete"
    />
</template>

<script setup>
import {
    nextTick,
    onBeforeUnmount,
    ref,
    watch,
} from 'vue';
import { useRouter } from 'vue-router';
import StatusBadge from '../servers/components/StatusBadge.vue';
import AccessListCard from './components/AccessListCard.vue';
import DeleteConfirmationModal from './components/DeleteConfirmationModal.vue';
import NicknameModal from './components/NicknameModal.vue';
import PlayersSummaryCard from './components/PlayersSummaryCard.vue';
import ResourcesSummaryCard from './components/ResourcesSummaryCard.vue';
import ServerConsole from './components/ServerConsole.vue';
import ServerControlsCard from './components/ServerControlsCard.vue';
import ServerSettingsModal from './components/ServerSettingsModal.vue';
import {
    addOperatorNickname,
    addWhitelistNickname,
    deleteMinecraftServer,
    deleteOperatorNickname,
    deleteWhitelistNickname,
    fetchMinecraftServer,
    fetchOperators,
    fetchWhitelist,
    startMinecraftServer,
    stopMinecraftServer,
} from './services/minecraftServerService';

const props = defineProps({
    serverId: {
        type: Number,
        required: true,
    },
});

const router = useRouter();
const STATUS_REFRESH_INTERVAL = 5000;

const server = ref(null);
const whitelist = ref([]);
const operators = ref([]);
const isLoading = ref(true);
const pageError = ref(null);
const isWhitelistLoading = ref(false);
const isOperatorsLoading = ref(false);
const whitelistError = ref('');
const operatorsError = ref('');
const isControlBusy = ref(false);
const controlError = ref('');
const isSettingsOpen = ref(false);
const nicknameTarget = ref(null);
const nicknameError = ref('');
const isAddingNickname = ref(false);
const deletionTarget = ref(null);
const deletionError = ref('');
const isDeleting = ref(false);
let statusRefreshTimer = null;

const errorMessage = (error, fallback) => (
    error.response?.data?.errors?.nickname?.[0]
    ?? error.response?.data?.message
    ?? fallback
);

const loadWhitelist = async () => {
    isWhitelistLoading.value = true;
    whitelistError.value = '';

    try {
        whitelist.value = await fetchWhitelist(props.serverId);
    } catch (error) {
        whitelistError.value = errorMessage(
            error,
            'Não foi possível carregar a whitelist.',
        );
    } finally {
        isWhitelistLoading.value = false;
    }
};

const loadOperators = async () => {
    isOperatorsLoading.value = true;
    operatorsError.value = '';

    try {
        operators.value = await fetchOperators(props.serverId);
    } catch (error) {
        operatorsError.value = errorMessage(
            error,
            'Não foi possível carregar os operators.',
        );
    } finally {
        isOperatorsLoading.value = false;
    }
};

const refreshServer = async () => {
    server.value = await fetchMinecraftServer(props.serverId);
};

const loadDashboard = async () => {
    isLoading.value = true;
    pageError.value = null;
    server.value = null;

    try {
        await refreshServer();
        await Promise.all([loadWhitelist(), loadOperators()]);
    } catch (error) {
        if (error.response?.status === 403) {
            pageError.value = {
                title: 'Acesso não autorizado',
                message: 'Você não possui acesso a este servidor.',
            };
        } else if (error.response?.status === 404) {
            pageError.value = {
                title: 'Servidor não encontrado',
                message: 'O servidor solicitado não existe.',
            };
        } else {
            pageError.value = {
                title: 'Não foi possível carregar o servidor',
                message: 'Tente novamente mais tarde.',
            };
        }
    } finally {
        isLoading.value = false;
    }
};

const refreshAfterControl = async () => {
    try {
        await refreshServer();
    } catch {
        controlError.value = 'Não foi possível atualizar o status do servidor.';
    }
};

const handleStart = async () => {
    isControlBusy.value = true;
    controlError.value = '';

    try {
        await startMinecraftServer(props.serverId);
        await refreshAfterControl();
    } catch (error) {
        controlError.value = errorMessage(
            error,
            'Não foi possível iniciar o servidor.',
        );
    } finally {
        isControlBusy.value = false;
    }
};

const handleStop = async () => {
    isControlBusy.value = true;
    controlError.value = '';

    try {
        await stopMinecraftServer(props.serverId);
        await refreshAfterControl();
    } catch (error) {
        controlError.value = errorMessage(
            error,
            'Não foi possível parar o servidor.',
        );
    } finally {
        isControlBusy.value = false;
    }
};

const handleSettingsUpdated = async () => {
    isSettingsOpen.value = false;
    await refreshAfterControl();
};

const openServerDeletion = async () => {
    if (!server.value || server.value.status !== 'stopped') {
        return;
    }

    isSettingsOpen.value = false;
    await nextTick();
    deletionError.value = '';
    deletionTarget.value = {
        type: 'server',
        id: server.value.id,
        resourceLabel: 'servidor',
        resourceName: server.value.name,
        detail: `Minecraft ${server.value.version?.version ?? ''}`.trim(),
    };
};

const openAccessDeletion = (type, entry) => {
    if (!server.value || server.value.status !== 'stopped') {
        return;
    }

    deletionError.value = '';
    deletionTarget.value = {
        type,
        id: entry.id,
        resourceLabel: type === 'operators' ? 'operator' : 'nickname da whitelist',
        resourceName: entry.nickname,
        detail: `Servidor: ${server.value.name}`,
    };
};

const closeDeletionModal = () => {
    if (!isDeleting.value) {
        deletionTarget.value = null;
        deletionError.value = '';
    }
};

const handleDelete = async () => {
    const target = deletionTarget.value;

    if (!target || isDeleting.value) {
        return;
    }

    isDeleting.value = true;
    deletionError.value = '';

    try {
        if (target.type === 'server') {
            await deleteMinecraftServer(props.serverId);
            deletionTarget.value = null;
            await router.push({ name: 'servers.index' });
            return;
        }

        if (target.type === 'operators') {
            await deleteOperatorNickname(props.serverId, target.id);
            await loadOperators();
        } else {
            await deleteWhitelistNickname(props.serverId, target.id);
            await loadWhitelist();
        }

        deletionTarget.value = null;
    } catch (error) {
        deletionError.value = errorMessage(
            error,
            'Não foi possível excluir o recurso.',
        );
    } finally {
        isDeleting.value = false;
    }
};

const openNicknameModal = (target) => {
    nicknameError.value = '';
    nicknameTarget.value = target;
};

const closeNicknameModal = () => {
    if (!isAddingNickname.value) {
        nicknameTarget.value = null;
        nicknameError.value = '';
    }
};

const handleNicknameSubmit = async (nickname) => {
    if (!nicknameTarget.value || isAddingNickname.value) {
        return;
    }

    isAddingNickname.value = true;
    nicknameError.value = '';

    try {
        if (nicknameTarget.value === 'operators') {
            await addOperatorNickname(props.serverId, nickname);
            await loadOperators();
        } else {
            await addWhitelistNickname(props.serverId, nickname);
            await loadWhitelist();
        }

        nicknameTarget.value = null;
    } catch (error) {
        nicknameError.value = errorMessage(
            error,
            'Não foi possível adicionar o nickname.',
        );
    } finally {
        isAddingNickname.value = false;
    }
};

const stopStatusRefresh = () => {
    if (statusRefreshTimer) {
        window.clearInterval(statusRefreshTimer);
        statusRefreshTimer = null;
    }
};

const startStatusRefresh = () => {
    stopStatusRefresh();
    statusRefreshTimer = window.setInterval(async () => {
        if (!server.value || isControlBusy.value) {
            return;
        }

        try {
            await refreshServer();
        } catch {
            // Keep the last confirmed server state when a refresh fails.
        }
    }, STATUS_REFRESH_INTERVAL);
};

watch(
    () => props.serverId,
    async () => {
        stopStatusRefresh();
        await loadDashboard();

        if (!pageError.value) {
            startStatusRefresh();
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    stopStatusRefresh();
});
</script>
