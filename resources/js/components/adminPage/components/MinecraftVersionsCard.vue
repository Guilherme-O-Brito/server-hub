<template>
    <article class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="absolute inset-y-0 left-0 w-1 bg-linear-to-b from-indigo-600 to-purple-600" aria-hidden="true"></div>

        <header class="flex flex-col gap-4 border-b border-gray-200 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 7.5 12 4.5l5.25 3v6L12 16.5l-5.25-3v-6Zm0 0L12 10.5m0 6v-6m5.25-3L12 10.5m-8.25 3L12 18l8.25-4.5M12 22.5v-4.5" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Versões do Minecraft</h2>
                    <p class="mt-1 text-sm text-gray-500">Versões disponíveis para os servidores.</p>
                </div>
            </div>

            <button
                type="button"
                class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2"
                @click="openCreateModal"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                </svg>
                Nova versão
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
                <table class="w-full min-w-[38rem] border-collapse text-left">
                    <thead class="bg-gray-100 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Versão</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <template v-if="isLoading">
                            <tr v-for="index in 3" :key="index">
                                <td v-for="column in 3" :key="column" class="px-4 py-4">
                                    <div class="h-5 animate-pulse rounded bg-gray-200"></div>
                                </td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr v-for="minecraftVersion in versions" :key="minecraftVersion.id" class="text-sm text-gray-700">
                                <td class="px-4 py-4 font-mono font-semibold text-gray-900">
                                    {{ minecraftVersion.version }}
                                </td>
                                <td class="px-4 py-4">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="minecraftVersion.isEnabled"
                                        :aria-label="`${minecraftVersion.isEnabled ? 'Desabilitar' : 'Habilitar'} versão ${minecraftVersion.version}`"
                                        class="inline-flex cursor-pointer items-center gap-2 rounded-full px-2 py-1 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                        :class="minecraftVersion.isEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
                                        :disabled="togglingIds.has(minecraftVersion.id)"
                                        @click="toggleVersion(minecraftVersion)"
                                    >
                                        <span
                                            class="relative h-5 w-9 rounded-full transition"
                                            :class="minecraftVersion.isEnabled ? 'bg-green-600' : 'bg-gray-400'"
                                            aria-hidden="true"
                                        >
                                            <span
                                                class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition"
                                                :class="minecraftVersion.isEnabled ? 'left-4.5' : 'left-0.5'"
                                            ></span>
                                        </span>
                                        {{ minecraftVersion.isEnabled ? 'Ativa' : 'Inativa' }}
                                    </button>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <button
                                        type="button"
                                        class="h-9 cursor-pointer rounded-lg bg-red-700 px-3 text-xs font-semibold text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
                                        @click="openDeleteModal(minecraftVersion)"
                                    >
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!versions.length">
                                <td colspan="3" class="px-6 py-12 text-center text-sm text-gray-500">
                                    Nenhuma versão cadastrada.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    <VersionFormModal
        :open="isCreateModalOpen"
        :submitting="isCreating"
        :error="createError"
        :field-errors="fieldErrors"
        @close="closeCreateModal"
        @submit="saveVersion"
    />

    <ActionConfirmationModal
        :open="Boolean(versionToDelete)"
        title="Excluir versão?"
        description="A versão deixará de estar disponível para novos servidores."
        resource-label="Versão do Minecraft"
        :resource-name="versionToDelete?.version ?? ''"
        confirm-label="Excluir versão"
        submitting-label="Excluindo..."
        danger
        :submitting="isDeleting"
        :error="deleteError"
        @close="closeDeleteModal"
        @confirm="confirmDelete"
    />
</template>

<script setup>
import { onMounted, ref } from 'vue';
import {
    createMinecraftVersion,
    deleteMinecraftVersion,
    fetchMinecraftVersions,
    toggleMinecraftVersion,
} from '../services/adminPageService';
import ActionConfirmationModal from './ActionConfirmationModal.vue';
import VersionFormModal from './VersionFormModal.vue';

const versions = ref([]);
const isLoading = ref(true);
const loadError = ref('');
const actionMessage = ref('');
const togglingIds = ref(new Set());
const isCreateModalOpen = ref(false);
const isCreating = ref(false);
const createError = ref('');
const fieldErrors = ref({});
const versionToDelete = ref(null);
const isDeleting = ref(false);
const deleteError = ref('');

const errorMessage = (error, fallback) => error.response?.data?.message ?? fallback;

const loadVersions = async () => {
    isLoading.value = true;
    loadError.value = '';

    try {
        versions.value = await fetchMinecraftVersions();
    } catch (error) {
        loadError.value = errorMessage(error, 'Não foi possível carregar as versões do Minecraft.');
    } finally {
        isLoading.value = false;
    }
};

const toggleVersion = async (minecraftVersion) => {
    if (togglingIds.value.has(minecraftVersion.id)) {
        return;
    }

    togglingIds.value = new Set([...togglingIds.value, minecraftVersion.id]);
    loadError.value = '';
    actionMessage.value = '';

    try {
        await toggleMinecraftVersion(minecraftVersion.id);
        minecraftVersion.isEnabled = !minecraftVersion.isEnabled;
        actionMessage.value = `Versão ${minecraftVersion.version} atualizada com sucesso.`;
    } catch (error) {
        loadError.value = errorMessage(error, 'Não foi possível alterar o status da versão.');
    } finally {
        const nextIds = new Set(togglingIds.value);
        nextIds.delete(minecraftVersion.id);
        togglingIds.value = nextIds;
    }
};

const openCreateModal = () => {
    createError.value = '';
    fieldErrors.value = {};
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    if (!isCreating.value) {
        isCreateModalOpen.value = false;
    }
};

const saveVersion = async (payload) => {
    if (isCreating.value) {
        return;
    }

    isCreating.value = true;
    createError.value = '';
    fieldErrors.value = {};

    try {
        await createMinecraftVersion(payload);
        isCreateModalOpen.value = false;
        actionMessage.value = 'Versão criada com sucesso.';
        await loadVersions();
    } catch (error) {
        if (error.response?.status === 422) {
            fieldErrors.value = error.response.data.errors ?? {};
            createError.value = 'Revise os campos destacados antes de continuar.';
        } else {
            createError.value = errorMessage(error, 'Não foi possível criar a versão.');
        }
    } finally {
        isCreating.value = false;
    }
};

const openDeleteModal = (minecraftVersion) => {
    versionToDelete.value = minecraftVersion;
    deleteError.value = '';
};

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        versionToDelete.value = null;
        deleteError.value = '';
    }
};

const confirmDelete = async () => {
    if (!versionToDelete.value || isDeleting.value) {
        return;
    }

    isDeleting.value = true;
    deleteError.value = '';

    try {
        const deletedId = versionToDelete.value.id;
        await deleteMinecraftVersion(deletedId);
        versions.value = versions.value.filter((version) => version.id !== deletedId);
        versionToDelete.value = null;
        actionMessage.value = 'Versão excluída com sucesso.';
    } catch (error) {
        deleteError.value = errorMessage(error, 'Não foi possível excluir a versão.');
    } finally {
        isDeleting.value = false;
    }
};

onMounted(loadVersions);
</script>
