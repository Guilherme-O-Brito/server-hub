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
                aria-labelledby="user-form-title"
                class="relative max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-indigo-600 to-purple-600" aria-hidden="true"></div>

                <header class="flex items-start justify-between gap-4 border-b border-gray-200 p-6">
                    <div>
                        <h2 id="user-form-title" class="text-2xl font-black text-gray-900">
                            {{ user ? 'Editar usuário' : 'Novo usuário' }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ user ? 'Atualize os dados e as permissões da conta.' : 'Cadastre uma nova conta na plataforma.' }}
                        </p>
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
                        <span class="mb-2 block text-sm font-semibold text-gray-700">Nome completo</span>
                        <input
                            ref="firstInput"
                            v-model.trim="form.name"
                            type="text"
                            name="name"
                            maxlength="255"
                            required
                            class="h-11 w-full rounded-xl border bg-gray-100 px-4 text-sm text-gray-900 outline-none transition focus:bg-white focus:ring-2 focus:ring-purple-100"
                            :class="fieldError('name') ? 'border-red-600' : 'border-gray-200 focus:border-purple-600'"
                            :disabled="submitting"
                        />
                        <span v-if="fieldError('name')" class="mt-1 block text-xs font-medium text-red-600">
                            {{ fieldError('name') }}
                        </span>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-gray-700">Email</span>
                        <input
                            v-model.trim="form.email"
                            type="email"
                            name="email"
                            maxlength="255"
                            required
                            autocomplete="off"
                            class="h-11 w-full rounded-xl border bg-gray-100 px-4 text-sm text-gray-900 outline-none transition focus:bg-white focus:ring-2 focus:ring-purple-100"
                            :class="fieldError('email') ? 'border-red-600' : 'border-gray-200 focus:border-purple-600'"
                            :disabled="submitting"
                        />
                        <span v-if="fieldError('email')" class="mt-1 block text-xs font-medium text-red-600">
                            {{ fieldError('email') }}
                        </span>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-gray-700">
                            {{ user ? 'Nova senha' : 'Senha' }}
                        </span>
                        <input
                            v-model="form.password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            :required="!user"
                            class="h-11 w-full rounded-xl border bg-gray-100 px-4 text-sm text-gray-900 outline-none transition focus:bg-white focus:ring-2 focus:ring-purple-100"
                            :class="fieldError('password') ? 'border-red-600' : 'border-gray-200 focus:border-purple-600'"
                            :disabled="submitting"
                        />
                        <span v-if="user" class="mt-1 block text-xs text-gray-500">
                            Deixe em branco para manter a senha atual.
                        </span>
                        <span v-if="fieldError('password')" class="mt-1 block text-xs font-medium text-red-600">
                            {{ fieldError('password') }}
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-gray-100 p-4">
                        <input
                            v-model="form.isAdmin"
                            type="checkbox"
                            name="is_admin"
                            class="mt-0.5 h-5 w-5 accent-purple-600"
                            :disabled="submitting"
                        />
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">Administrador da plataforma</span>
                            <span class="mt-1 block text-xs text-gray-500">
                                Permite acessar e alterar configurações globais.
                            </span>
                        </span>
                    </label>

                    <span v-if="fieldError('is_admin')" class="block text-xs font-medium text-red-600">
                        {{ fieldError('is_admin') }}
                    </span>

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
                            {{ submitting ? 'Salvando...' : 'Salvar usuário' }}
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
    user: {
        type: Object,
        default: null,
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
    name: '',
    email: '',
    password: '',
    isAdmin: false,
});
let previousBodyOverflow = '';

const fieldError = (field) => props.fieldErrors[field]?.[0] ?? '';

const resetForm = () => {
    Object.assign(form, {
        name: props.user?.name ?? '',
        email: props.user?.email ?? '',
        password: '',
        isAdmin: Boolean(props.user?.isAdmin),
    });
};

const submit = () => {
    emit('submit', {
        name: form.name,
        email: form.email,
        password: form.password || null,
        is_admin: form.isAdmin,
    });
};

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
            resetForm();
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
