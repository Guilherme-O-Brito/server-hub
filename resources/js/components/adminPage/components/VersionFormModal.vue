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
                aria-labelledby="version-form-title"
                class="relative w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-indigo-600 to-purple-600" aria-hidden="true"></div>

                <header class="flex items-start justify-between gap-4 border-b border-gray-200 p-6">
                    <div>
                        <h2 id="version-form-title" class="text-2xl font-black text-gray-900">Nova versão</h2>
                        <p class="mt-1 text-sm text-gray-500">Cadastre uma versão disponível para novos servidores.</p>
                    </div>
                    <button
                        type="button"
                        class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl text-gray-500 transition hover:bg-gray-100 hover:text-purple-700 disabled:cursor-not-allowed disabled:opacity-60"
                        aria-label="Fechar modal"
                        :disabled="submitting"
                        @click="requestClose"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </header>

                <form class="space-y-5 p-6" @submit.prevent="submit">
                    <p v-if="error" class="rounded-xl border border-red-200 bg-red-100 p-4 text-sm font-medium text-red-700" role="alert">
                        {{ error }}
                    </p>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-gray-700">Versão do Minecraft</span>
                        <input
                            ref="firstInput"
                            v-model.trim="form.version"
                            type="text"
                            name="version"
                            maxlength="8"
                            pattern="\d+(\.\d+){1,2}"
                            placeholder="1.21.4"
                            required
                            class="h-11 w-full rounded-xl border bg-gray-100 px-4 font-mono text-sm text-gray-900 outline-none transition focus:bg-white focus:ring-2 focus:ring-purple-100"
                            :class="fieldError('version') ? 'border-red-600' : 'border-gray-200 focus:border-purple-600'"
                            :disabled="submitting"
                        />
                        <span v-if="fieldError('version')" class="mt-1 block text-xs font-medium text-red-600">
                            {{ fieldError('version') }}
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-gray-100 p-4">
                        <input
                            v-model="form.isEnabled"
                            type="checkbox"
                            name="is_enabled"
                            class="mt-0.5 h-5 w-5 accent-purple-600"
                            :disabled="submitting"
                        />
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">Versão habilitada</span>
                            <span class="mt-1 block text-xs text-gray-500">Permite selecionar esta versão ao configurar servidores.</span>
                        </span>
                    </label>

                    <footer class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="h-11 cursor-pointer rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="submitting"
                            @click="requestClose"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="h-11 cursor-pointer rounded-xl bg-purple-700 px-5 text-sm font-semibold text-white transition hover:bg-purple-800 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="submitting"
                        >
                            {{ submitting ? 'Criando...' : 'Criar versão' }}
                        </button>
                    </footer>
                </form>
            </section>
        </div>
    </Teleport>
</template>

<script setup>
import {
    nextTick,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue';

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    submitting: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
    fieldErrors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['close', 'submit']);
const firstInput = ref(null);
const form = reactive({
    version: '',
    isEnabled: true,
});
let previousBodyOverflow = '';

const fieldError = (field) => props.fieldErrors[field]?.[0] ?? '';

const requestClose = () => {
    if (!props.submitting) {
        emit('close');
    }
};

const submit = () => emit('submit', {
    version: form.version,
    is_enabled: form.isEnabled,
});

const handleKeydown = (event) => {
    if (props.open && event.key === 'Escape') {
        requestClose();
    }
};

watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            form.version = '';
            form.isEnabled = true;
            previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            await nextTick();
            firstInput.value?.focus();
            return;
        }

        document.body.style.overflow = previousBodyOverflow;
    },
);

onMounted(() => window.addEventListener('keydown', handleKeydown));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = previousBodyOverflow;
});
</script>
