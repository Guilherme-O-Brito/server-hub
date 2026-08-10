<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4"
            @mousedown.self="requestClose"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="admin-search-modal-title"
                class="relative flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <div
                    class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-indigo-600 to-purple-600"
                    aria-hidden="true"
                ></div>

                <header class="flex items-start justify-between gap-4 border-b border-gray-200 p-6">
                    <div>
                        <h2 id="admin-search-modal-title" class="text-2xl font-black text-gray-900">
                            Adicionar administrador
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Pesquise um usuário para conceder acesso ao servidor.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl text-gray-500 transition hover:bg-gray-100 hover:text-purple-700 disabled:cursor-not-allowed disabled:opacity-60"
                        aria-label="Fechar modal"
                        :disabled="addingUserId !== null"
                        @click="requestClose"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </header>

                <div class="min-h-0 overflow-y-auto p-6">
                    <form class="relative" role="search" @submit.prevent="loadUsers(1)">
                        <label for="admin-user-search" class="sr-only">Pesquisar usuários</label>
                        <svg
                            class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            id="admin-user-search"
                            ref="searchInput"
                            v-model="searchQuery"
                            type="search"
                            name="search"
                            maxlength="255"
                            autocomplete="off"
                            placeholder="Buscar por nome..."
                            class="h-11 w-full rounded-xl border border-gray-200 bg-gray-100 pl-11 pr-4 text-sm text-gray-900 outline-none transition focus:border-purple-600 focus:bg-white focus:ring-2 focus:ring-purple-100"
                            @input="scheduleSearch"
                        />
                    </form>

                    <p
                        v-if="actionMessage"
                        class="mt-4 rounded-xl border p-3 text-sm font-medium"
                        :class="actionMessage.type === 'success'
                            ? 'border-green-200 bg-green-100 text-green-700'
                            : 'border-red-200 bg-red-100 text-red-700'"
                        role="status"
                    >
                        {{ actionMessage.text }}
                    </p>

                    <p v-if="loadError" class="mt-4 text-sm font-medium text-red-600" role="alert">
                        {{ loadError }}
                    </p>

                    <div class="mt-5 overflow-hidden rounded-xl border border-gray-200 bg-gray-100">
                        <div v-if="isLoading" class="space-y-2 p-4" aria-label="Carregando usuários">
                            <div class="h-14 animate-pulse rounded-lg bg-gray-200"></div>
                            <div class="h-14 animate-pulse rounded-lg bg-gray-200"></div>
                            <div class="h-14 animate-pulse rounded-lg bg-gray-200"></div>
                        </div>

                        <ul v-else-if="users.length" class="divide-y divide-gray-200">
                            <li
                                v-for="user in users"
                                :key="user.id"
                                class="flex items-center justify-between gap-4 bg-white px-4 py-3"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
                                        </svg>
                                    </div>
                                    <span class="truncate text-sm font-semibold text-gray-900">
                                        {{ user.name }}
                                    </span>
                                </div>

                                <span
                                    v-if="user.id === ownerId"
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-100 text-purple-700"
                                    title="Proprietário do servidor"
                                    :aria-label="`${user.name} é o proprietário do servidor`"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3.75 7.5 3.75 3 4.5-6 4.5 6 3.75-3-1.5 10.5H5.25L3.75 7.5Z" />
                                    </svg>
                                </span>
                                <span
                                    v-else-if="isAdmin(user.id)"
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-700"
                                    title="Este usuário já é administrador"
                                    :aria-label="`${user.name} já é administrador`"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" />
                                    </svg>
                                </span>
                                <button
                                    v-else
                                    type="button"
                                    class="flex h-9 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-lg bg-purple-700 px-3 text-xs font-semibold text-white transition hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="addingUserId !== null"
                                    @click="addUser(user)"
                                >
                                    <svg v-if="addingUserId !== user.id" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                                    </svg>
                                    {{ addingUserId === user.id ? 'Adicionando...' : 'Adicionar' }}
                                </button>
                            </li>
                        </ul>

                        <p v-else class="px-4 py-10 text-center text-sm text-gray-500">
                            Nenhum usuário encontrado.
                        </p>
                    </div>

                    <nav
                        v-if="totalPages > 1"
                        class="mt-5 flex items-center justify-between gap-3"
                        aria-label="Paginação dos usuários"
                    >
                        <button
                            type="button"
                            class="h-10 cursor-pointer rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:border-purple-600 hover:text-purple-700 disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="isLoading || currentPage === 1"
                            @click="loadUsers(currentPage - 1)"
                        >
                            Anterior
                        </button>
                        <span class="text-xs font-semibold text-gray-500">
                            Página {{ currentPage }} de {{ totalPages }}
                        </span>
                        <button
                            type="button"
                            class="h-10 cursor-pointer rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:border-purple-600 hover:text-purple-700 disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="isLoading || currentPage === totalPages"
                            @click="loadUsers(currentPage + 1)"
                        >
                            Próxima
                        </button>
                    </nav>
                </div>
            </section>
        </div>
    </Teleport>
</template>

<script setup>
import {
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import {
    addMinecraftServerAdmin,
    fetchUsers,
} from '../services/minecraftServerService';

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    serverId: {
        type: Number,
        required: true,
    },
    ownerId: {
        type: Number,
        default: null,
    },
    admins: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'admin-added']);
const SEARCH_DELAY = 350;

const searchInput = ref(null);
const searchQuery = ref('');
const users = ref([]);
const currentPage = ref(1);
const totalPages = ref(1);
const isLoading = ref(false);
const loadError = ref('');
const addingUserId = ref(null);
const actionMessage = ref(null);
const locallyAddedIds = ref(new Set());
let searchTimer = null;
let requestSequence = 0;
let previousBodyOverflow = '';

const isAdmin = (userId) => (
    locallyAddedIds.value.has(userId)
    || props.admins.some((admin) => admin.id === userId)
);

const loadUsers = async (page = 1) => {
    window.clearTimeout(searchTimer);
    const requestId = ++requestSequence;
    isLoading.value = true;
    loadError.value = '';

    try {
        const result = await fetchUsers({
            search: searchQuery.value.trim(),
            page,
        });

        if (requestId !== requestSequence) {
            return;
        }

        users.value = result.users;
        currentPage.value = result.pagination.currentPage;
        totalPages.value = result.pagination.totalPages;
    } catch (error) {
        if (requestId === requestSequence) {
            loadError.value = error.response?.data?.message
                ?? 'Não foi possível carregar os usuários.';
        }
    } finally {
        if (requestId === requestSequence) {
            isLoading.value = false;
        }
    }
};

const scheduleSearch = () => {
    window.clearTimeout(searchTimer);
    actionMessage.value = null;
    searchTimer = window.setTimeout(() => loadUsers(1), SEARCH_DELAY);
};

const addUser = async (user) => {
    if (
        addingUserId.value !== null
        || user.id === props.ownerId
        || isAdmin(user.id)
    ) {
        return;
    }

    addingUserId.value = user.id;
    actionMessage.value = null;

    try {
        await addMinecraftServerAdmin(props.serverId, user.id);
        locallyAddedIds.value = new Set([...locallyAddedIds.value, user.id]);
        actionMessage.value = {
            type: 'success',
            text: `${user.name} foi adicionado como administrador.`,
        };
        emit('admin-added', user);
    } catch (error) {
        if (error.response?.status === 409) {
            locallyAddedIds.value = new Set([...locallyAddedIds.value, user.id]);
            emit('admin-added', user);
        }

        actionMessage.value = {
            type: 'error',
            text: error.response?.data?.message
                ?? 'Não foi possível adicionar o administrador.',
        };
    } finally {
        addingUserId.value = null;
    }
};

const requestClose = () => {
    if (addingUserId.value === null) {
        emit('close');
    }
};

const handleKeydown = (event) => {
    if (props.open && event.key === 'Escape') {
        requestClose();
    }
};

watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            searchQuery.value = '';
            users.value = [];
            currentPage.value = 1;
            totalPages.value = 1;
            loadError.value = '';
            actionMessage.value = null;
            locallyAddedIds.value = new Set();
            previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            await loadUsers(1);
            await nextTick();
            searchInput.value?.focus();
            return;
        }

        requestSequence += 1;
        window.clearTimeout(searchTimer);
        document.body.style.overflow = previousBodyOverflow;
    },
);

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.clearTimeout(searchTimer);
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = previousBodyOverflow;
});
</script>
