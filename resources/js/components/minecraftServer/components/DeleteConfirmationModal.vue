<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4"
            @mousedown.self="requestClose"
        >
            <section
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="delete-modal-title"
                aria-describedby="delete-modal-description"
                class="relative w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <div class="absolute inset-x-0 top-0 h-1 bg-red-700" aria-hidden="true"></div>

                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-200 text-red-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m14.74 9-.35 9m-4.78 0L9.26 9m9.97-3.21c.35.05.7.1 1.04.16m-1.04-.16L18.16 19.67A2.25 2.25 0 0 1 15.92 21H8.08a2.25 2.25 0 0 1-2.24-2.33L4.77 5.79m14.46 0a48.1 48.1 0 0 0-3.48-.4m-10.98.4c-.35.05-.7.1-1.04.16m1.04-.16a48.1 48.1 0 0 1 3.48-.4m7.5 0V4.47c0-1.18-.91-2.17-2.09-2.2a52.1 52.1 0 0 0-3.32 0c-1.18.03-2.09 1.02-2.09 2.2v.92m7.5 0a48.67 48.67 0 0 0-7.5 0" />
                            </svg>
                        </div>
                        <div>
                            <h2 id="delete-modal-title" class="text-xl font-black text-gray-900">
                                Excluir {{ resourceLabel }}?
                            </h2>
                            <p id="delete-modal-description" class="mt-1 text-sm text-gray-500">
                                Confirme a exclusão do recurso abaixo. Esta ação não pode ser desfeita.
                            </p>
                        </div>
                    </div>

                    <dl class="mt-5 rounded-xl border border-gray-200 bg-gray-100 p-4">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {{ resourceLabel }}
                            </dt>
                            <dd class="mt-1 wrap-break-word text-sm font-bold text-gray-900">
                                {{ resourceName }}
                            </dd>
                        </div>
                        <div v-if="detail" class="mt-3 border-t border-gray-200 pt-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Contexto
                            </dt>
                            <dd class="mt-1 text-sm text-gray-700">{{ detail }}</dd>
                        </div>
                    </dl>

                    <p v-if="error" class="mt-4 text-sm font-medium text-red-600" role="alert">
                        {{ error }}
                    </p>
                </div>

                <footer class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-100 px-6 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="h-11 cursor-pointer rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="submitting"
                        @click="requestClose"
                    >
                        Cancelar
                    </button>
                    <button
                        ref="confirmButton"
                        type="button"
                        class="h-11 cursor-pointer rounded-xl bg-red-700 px-5 text-sm font-semibold text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="submitting"
                        @click="$emit('confirm')"
                    >
                        {{ submitting ? 'Excluindo...' : 'Confirmar exclusão' }}
                    </button>
                </footer>
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

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    resourceLabel: {
        type: String,
        required: true,
    },
    resourceName: {
        type: String,
        required: true,
    },
    detail: {
        type: String,
        default: '',
    },
    submitting: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'confirm']);
const confirmButton = ref(null);
let previousBodyOverflow = '';

const requestClose = () => {
    if (!props.submitting) {
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
            previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            await nextTick();
            confirmButton.value?.focus();
            return;
        }

        document.body.style.overflow = previousBodyOverflow;
    },
);

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = previousBodyOverflow;
});
</script>
