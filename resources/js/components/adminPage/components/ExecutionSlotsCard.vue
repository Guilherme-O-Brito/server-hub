<template>
    <article class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="absolute inset-y-0 left-0 w-1 bg-linear-to-b from-indigo-600 to-purple-600" aria-hidden="true"></div>

        <header class="flex flex-col gap-4 border-b border-gray-200 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Slots de execução</h2>
                    <p class="mt-1 text-sm text-gray-500">Infraestrutura disponível para executar servidores.</p>
                </div>
            </div>

            <button
                type="button"
                class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2"
                @click="openCreateConfirmation"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                </svg>
                Novo slot
            </button>
        </header>

        <div class="p-6">
            <p v-if="actionMessage" class="mb-4 rounded-xl border border-green-200 bg-green-100 p-3 text-sm font-medium text-green-700" role="status">
                {{ actionMessage }}
            </p>
            <p v-if="error" class="mb-4 rounded-xl border border-red-200 bg-red-100 p-3 text-sm font-medium text-red-700" role="alert">
                {{ error }}
            </p>

            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full min-w-[72rem] border-collapse text-left">
                    <thead class="bg-gray-100 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Slot</th>
                            <th class="px-4 py-3">Hostname</th>
                            <th class="px-4 py-3">Porta externa</th>
                            <th class="px-4 py-3">Service name</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Servidor vinculado</th>
                            <th class="px-4 py-3">Jogo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <template v-if="loading">
                            <tr v-for="index in 3" :key="index">
                                <td v-for="column in 7" :key="column" class="px-4 py-4">
                                    <div class="h-5 animate-pulse rounded bg-gray-200"></div>
                                </td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr v-for="slot in slots" :key="slot.id" class="text-sm text-gray-700">
                                <td class="px-4 py-4 font-bold text-gray-900">{{ slot.slotNumber }}</td>
                                <td class="px-4 py-4 font-mono text-xs">{{ slot.hostname || '—' }}</td>
                                <td class="px-4 py-4 font-mono">{{ slot.externalPort || '—' }}</td>
                                <td class="px-4 py-4 font-mono text-xs">{{ slot.serviceName || '—' }}</td>
                                <td class="px-4 py-4">
                                    <StatusBadge :status="slot.status" context="slot" />
                                </td>
                                <td class="px-4 py-4 font-semibold text-gray-900">{{ slot.serverName || 'Não vinculado' }}</td>
                                <td class="px-4 py-4">{{ slot.gameName || '—' }}</td>
                            </tr>
                            <tr v-if="!slots.length">
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                                    Nenhum slot de execução cadastrado.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="flex justify-end border-t border-gray-200 bg-gray-100 px-6 py-4">
            <button
                type="button"
                class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-red-700 px-4 text-sm font-semibold text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="loading || !lastSlot"
                @click="openDeleteConfirmation"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m14.74 9-.35 9m-4.78 0L9.26 9m9.97-3.21L18.16 19.67A2.25 2.25 0 0 1 15.92 21H8.08a2.25 2.25 0 0 1-2.24-2.33L4.77 5.79m14.46 0a48.67 48.67 0 0 0-14.46 0m10.98-.4V4.47c0-1.18-.91-2.17-2.09-2.2a52.1 52.1 0 0 0-3.32 0c-1.18.03-2.09 1.02-2.09 2.2v.92" />
                </svg>
                Excluir slot {{ lastSlot?.slotNumber ?? '' }}
            </button>
        </footer>
    </article>

    <ActionConfirmationModal
        :open="confirmationAction === 'create'"
        title="Criar novo slot?"
        description="Um novo slot de execução será provisionado com a próxima numeração disponível."
        resource-label="Próximo slot"
        :resource-name="`Slot ${nextSlotNumber}`"
        confirm-label="Criar slot"
        submitting-label="Criando..."
        :submitting="isSubmitting"
        :error="confirmationError"
        @close="closeConfirmation"
        @confirm="confirmCreate"
    />

    <ActionConfirmationModal
        :open="confirmationAction === 'delete'"
        title="Excluir último slot?"
        description="Somente o slot de maior número pode ser removido. A operação não poderá ser desfeita."
        resource-label="Slot de execução"
        :resource-name="lastSlot ? `Slot ${lastSlot.slotNumber} · ${lastSlot.hostname}` : ''"
        confirm-label="Excluir slot"
        submitting-label="Excluindo..."
        danger
        :submitting="isSubmitting"
        :error="confirmationError"
        @close="closeConfirmation"
        @confirm="confirmDelete"
    />
</template>

<script setup>
import { computed, ref } from 'vue';
import StatusBadge from '../../servers/components/StatusBadge.vue';
import {
    createExecutionSlot,
    deleteLastExecutionSlot,
} from '../services/adminPageService';
import ActionConfirmationModal from './ActionConfirmationModal.vue';

const props = defineProps({
    slots: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['changed']);
const confirmationAction = ref(null);
const isSubmitting = ref(false);
const confirmationError = ref('');
const actionMessage = ref('');

const lastSlot = computed(() => props.slots.reduce((highestSlot, slot) => (
    !highestSlot || slot.slotNumber > highestSlot.slotNumber ? slot : highestSlot
), null));

const nextSlotNumber = computed(() => (lastSlot.value?.slotNumber ?? 0) + 1);
const errorMessage = (error, fallback) => error.response?.data?.message ?? fallback;

const openCreateConfirmation = () => {
    confirmationError.value = '';
    confirmationAction.value = 'create';
};

const openDeleteConfirmation = () => {
    if (!lastSlot.value) {
        return;
    }

    confirmationError.value = '';
    confirmationAction.value = 'delete';
};

const closeConfirmation = () => {
    if (!isSubmitting.value) {
        confirmationAction.value = null;
        confirmationError.value = '';
    }
};

const confirmCreate = async () => {
    if (isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;
    confirmationError.value = '';

    try {
        await createExecutionSlot();
        confirmationAction.value = null;
        actionMessage.value = 'Slot criado com sucesso.';
        emit('changed');
    } catch (error) {
        confirmationError.value = errorMessage(error, 'Não foi possível criar o slot.');
    } finally {
        isSubmitting.value = false;
    }
};

const confirmDelete = async () => {
    if (!lastSlot.value || isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;
    confirmationError.value = '';

    try {
        await deleteLastExecutionSlot();
        confirmationAction.value = null;
        actionMessage.value = 'Slot excluído com sucesso.';
        emit('changed');
    } catch (error) {
        confirmationError.value = errorMessage(error, 'Não foi possível excluir o último slot.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>
