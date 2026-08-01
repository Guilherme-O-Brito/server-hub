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
            <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
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

                <ConnectedUserCard :user="auth.user"/>
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
                    @settings="isSettingsOpen = true"
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
    inject,
    nextTick,
    onBeforeUnmount,
    ref,
    watch,
} from 'vue';
import { useRouter } from 'vue-router';
import ConnectedUserCard from '../ConnectedUserCard.vue';
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
const auth = inject('auth');
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
    if (!server.value) {
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
