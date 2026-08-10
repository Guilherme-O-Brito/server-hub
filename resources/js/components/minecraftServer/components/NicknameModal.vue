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
                aria-labelledby="nickname-modal-title"
                class="relative w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <div
                    class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-indigo-600 to-purple-600"
                    aria-hidden="true"
                ></div>

                <header class="flex items-start justify-between gap-4 border-b border-gray-200 p-6">
                    <div>
                        <h2 id="nickname-modal-title" class="text-xl font-black text-gray-900">
                            {{ title }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Informe um nickname válido do Minecraft.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-xl text-gray-500 transition hover:bg-gray-100 hover:text-purple-700"
                        aria-label="Fechar modal"
                        :disabled="submitting"
                        @click="requestClose"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </header>

                <form class="p-6" @submit.prevent="submit">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-gray-700">
                            Nickname
                        </span>
                        <input
                            ref="nicknameInput"
                            v-model.trim="nickname"
                            type="text"
                            name="nickname"
                            maxlength="16"
                            pattern="[A-Za-z0-9_]+"
                            required
                            autocomplete="off"
                            placeholder="Ex.: Steve_01"
                            class="h-11 w-full rounded-xl border bg-gray-100 px-4 text-sm text-gray-900 outline-none transition focus:bg-white focus:ring-2 focus:ring-purple-100"
                            :class="error ? 'border-red-600' : 'border-gray-200 focus:border-purple-600'"
                            :disabled="submitting"
                        />
                        <span class="mt-1 block text-xs text-gray-500">
                            Até 16 caracteres: letras, números e underline.
                        </span>
                    </label>

                    <p v-if="error" class="mt-3 text-sm font-medium text-red-600" role="alert">
                        {{ error }}
                    </p>

                    <footer class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
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
                            {{ submitting ? 'Adicionando...' : 'Adicionar nickname' }}
                        </button>
                    </footer>
                </form>
            </section>
        </div>
    </Teleport>
</template>

<script setup>
import {
    computed,
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
    target: {
        type: String,
        default: 'whitelist',
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

const emit = defineEmits(['close', 'submit']);

const nickname = ref('');
const nicknameInput = ref(null);
let previousBodyOverflow = '';

const title = computed(() => (
    props.target === 'operators'
        ? 'Adicionar operator'
        : 'Adicionar à whitelist'
));

const requestClose = () => {
    if (!props.submitting) {
        emit('close');
    }
};

const submit = () => {
    emit('submit', nickname.value);
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
            nickname.value = '';
            previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            await nextTick();
            nicknameInput.value?.focus();
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
