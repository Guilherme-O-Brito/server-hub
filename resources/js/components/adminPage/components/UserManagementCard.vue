<template>
    <article class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="absolute inset-y-0 left-0 w-1 bg-linear-to-b from-indigo-600 to-purple-600" aria-hidden="true"></div>

        <header class="grid grid-cols-1 gap-4 border-b border-gray-200 p-6 lg:grid-cols-[auto_minmax(16rem,1fr)_auto] lg:items-end">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19.1a7.5 7.5 0 0 0-6 0m9-2.1a4.5 4.5 0 0 1 3 4.25M6 17a4.5 4.5 0 0 0-3 4.25M12 15.25a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5Zm5.5-7.5a2.5 2.5 0 0 1 0 5M6.5 7.75a2.5 2.5 0 0 0 0 5"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Usuários</h2>
                    <p class="mt-1 text-sm text-gray-500">Contas e permissões globais.</p>
                </div>
            </div>

            <label class="relative block">
                <span class="mb-2 block text-sm font-semibold text-gray-700">Buscar usuário</span>
                <span class="pointer-events-none absolute bottom-3 left-3 text-gray-500" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                    </svg>
                </span>
                <input
                    v-model="searchQuery"
                    type="search"
                    maxlength="255"
                    placeholder="Pesquisar por nome..."
                    class="h-11 w-full rounded-xl border border-gray-200 bg-gray-100 py-2 pl-10 pr-4 text-sm text-gray-900 outline-none transition focus:border-purple-600 focus:bg-white focus:ring-2 focus:ring-purple-100"
                    @input="scheduleSearch"
                />
            </label>

            <button
                type="button"
                class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2"
                @click="openCreateModal"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                </svg>
                Novo usuário
            </button>
        </header>

        <div class="p-6">
            <p v-if="actionMessage" class="mb-4 rounded-xl border border-green-200 bg-green-100 p-3 text-sm font-medium text-green-700" role="status">
                {{ actionMessage }}
            </p>
            <p v-if="loadError" class="mb-4 rounded-xl border border-red-200 bg-red-100 p-3 text-sm font-medium text-red-700" role="alert">
                {{ loadError }}
            </p>

            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full min-w-4xl border-collapse text-left">
                    <thead class="bg-gray-100 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Nome completo</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Papel</th>
                            <th class="px-4 py-3">Criado em</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <template v-if="isLoading">
                            <tr v-for="index in 4" :key="index">
                                <td v-for="column in 5" :key="column" class="px-4 py-4">
                                    <div class="h-5 animate-pulse rounded bg-gray-200"></div>
                                </td>
                            </tr>
                        </template>
                        <tr v-for="user in users" v-else :key="user.id" class="text-sm text-gray-700">
                            <td class="px-4 py-4 font-semibold text-gray-900">{{ user.name }}</td>
                            <td class="px-4 py-4">{{ user.email ?? '—' }}</td>
                            <td class="px-4 py-4">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="user.isAdmin === true
                                        ? 'bg-purple-100 text-purple-700'
                                        : user.isAdmin === false
                                            ? 'bg-gray-200 text-gray-700'
                                            : 'bg-amber-100 text-amber-700'"
                                >
                                    {{ user.isAdmin === true ? 'Administrador' : user.isAdmin === false ? 'Usuário' : 'Não informado' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">{{ formatDate(user.createdAt) }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="flex h-9 cursor-pointer items-center justify-center gap-2 rounded-lg bg-blue-700 px-3 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"
                                        @click="openEditModal(user)"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        type="button"
                                        class="flex h-9 cursor-pointer items-center justify-center gap-2 rounded-lg bg-red-700 px-3 text-xs font-semibold text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
                                        @click="openDeleteModal(user)"
                                    >
                                        Excluir
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!isLoading && !users.length">
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                Nenhum usuário encontrado.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <AdminPagination
                v-if="totalPages > 1"
                class="mt-6"
                :current-page="currentPage"
                :total-pages="totalPages"
                :disabled="isLoading"
                @change="loadUsers"
            />
        </div>
    </article>

    <UserFormModal
        :open="isFormOpen"
        :user="selectedUser"
        :submitting="isSaving"
        :error="formError"
        :field-errors="fieldErrors"
        @close="closeFormModal"
        @submit="saveUser"
    />

    <ActionConfirmationModal
        :open="Boolean(userToDelete)"
        title="Excluir usuário?"
        description="A conta será removida permanentemente da plataforma."
        resource-label="Usuário"
        :resource-name="userToDelete?.name ?? ''"
        confirm-label="Excluir usuário"
        submitting-label="Excluindo..."
        danger
        :submitting="isDeleting"
        :error="deleteError"
        @close="closeDeleteModal"
        @confirm="confirmDelete"
    />
</template>

<script setup>
import {
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue';
import {
    createUser,
    deleteUser,
    fetchAdminUsers,
    updateUser,
} from '../services/adminPageService';
import ActionConfirmationModal from './ActionConfirmationModal.vue';
import AdminPagination from './AdminPagination.vue';
import UserFormModal from './UserFormModal.vue';

const SEARCH_DELAY = 350;
const users = ref([]);
const searchQuery = ref('');
const currentPage = ref(1);
const totalPages = ref(1);
const isLoading = ref(true);
const loadError = ref('');
const actionMessage = ref('');
const isFormOpen = ref(false);
const selectedUser = ref(null);
const isSaving = ref(false);
const formError = ref('');
const fieldErrors = ref({});
const userToDelete = ref(null);
const isDeleting = ref(false);
const deleteError = ref('');
let searchTimer = null;
let loadSequence = 0;

const errorMessage = (error, fallback) => error.response?.data?.message ?? fallback;

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? '—'
        : date.toLocaleDateString('pt-BR');
};

const loadUsers = async (page = 1, force = false) => {
    if (
        page < 1
        || page > totalPages.value
        || (!force && page === currentPage.value && !isLoading.value)
    ) {
        return;
    }

    window.clearTimeout(searchTimer);
    const requestId = ++loadSequence;
    isLoading.value = true;
    loadError.value = '';

    try {
        const result = await fetchAdminUsers({
            search: searchQuery.value.trim(),
            page,
        });

        if (requestId !== loadSequence) {
            return;
        }

        users.value = result.users;
        currentPage.value = result.pagination.currentPage;
        totalPages.value = result.pagination.totalPages;
    } catch (error) {
        if (requestId === loadSequence) {
            loadError.value = errorMessage(error, 'Não foi possível carregar os usuários.');
        }
    } finally {
        if (requestId === loadSequence) {
            isLoading.value = false;
        }
    }
};

const scheduleSearch = () => {
    window.clearTimeout(searchTimer);
    actionMessage.value = '';
    searchTimer = window.setTimeout(() => loadUsers(1, true), SEARCH_DELAY);
};

const openCreateModal = () => {
    selectedUser.value = null;
    formError.value = '';
    fieldErrors.value = {};
    isFormOpen.value = true;
};

const openEditModal = (user) => {
    selectedUser.value = user;
    formError.value = '';
    fieldErrors.value = {};
    isFormOpen.value = true;
};

const closeFormModal = () => {
    if (!isSaving.value) {
        isFormOpen.value = false;
        selectedUser.value = null;
    }
};

const saveUser = async (payload) => {
    if (isSaving.value) {
        return;
    }

    isSaving.value = true;
    formError.value = '';
    fieldErrors.value = {};

    try {
        if (selectedUser.value) {
            await updateUser(selectedUser.value.id, payload);
            actionMessage.value = 'Usuário atualizado com sucesso.';
        } else {
            await createUser(payload);
            actionMessage.value = 'Usuário criado com sucesso.';
        }

        isFormOpen.value = false;
        selectedUser.value = null;
        await loadUsers(currentPage.value, true);
    } catch (error) {
        if (error.response?.status === 422) {
            fieldErrors.value = error.response.data.errors ?? {};
            formError.value = 'Revise os campos destacados antes de continuar.';
        } else {
            formError.value = errorMessage(error, 'Não foi possível salvar o usuário.');
        }
    } finally {
        isSaving.value = false;
    }
};

const openDeleteModal = (user) => {
    userToDelete.value = user;
    deleteError.value = '';
};

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        userToDelete.value = null;
        deleteError.value = '';
    }
};

const confirmDelete = async () => {
    if (!userToDelete.value || isDeleting.value) {
        return;
    }

    isDeleting.value = true;
    deleteError.value = '';

    try {
        await deleteUser(userToDelete.value.id);
        userToDelete.value = null;
        actionMessage.value = 'Usuário excluído com sucesso.';
        await loadUsers(currentPage.value, true);

        if (!users.value.length && currentPage.value > 1) {
            await loadUsers(currentPage.value - 1, true);
        }
    } catch (error) {
        deleteError.value = error.response?.status === 403
            ? 'O usuário autenticado não pode excluir a própria conta.'
            : errorMessage(error, 'Não foi possível excluir o usuário.');
    } finally {
        isDeleting.value = false;
    }
};

onMounted(() => loadUsers());

onBeforeUnmount(() => {
    window.clearTimeout(searchTimer);
    loadSequence += 1;
});
</script>
