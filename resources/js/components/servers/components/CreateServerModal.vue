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
                aria-labelledby="create-server-title"
                class="relative max-h-screen w-full max-w-2xl overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <div
                    class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-indigo-600 to-purple-600"
                    aria-hidden="true"
                ></div>

                <header class="flex items-start justify-between gap-4 border-b border-gray-200 p-6">
                    <div>
                        <h2 id="create-server-title" class="text-2xl font-black text-gray-900">
                            Criar servidor
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Escolha o jogo e configure os dados iniciais do servidor.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl text-gray-500 transition hover:bg-gray-100 hover:text-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-600"
                        aria-label="Fechar modal"
                        :disabled="isSubmitting"
                        @click="requestClose"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </header>

                <form class="space-y-6 p-6" @submit.prevent="submit">
                    <div
                        v-if="generalError"
                        class="rounded-xl border border-red-200 bg-red-200 p-4 text-sm font-medium text-red-600"
                        role="alert"
                    >
                        {{ generalError }}
                    </div>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-gray-700">
                            Jogo
                        </span>
                        <select
                            ref="gameSelect"
                            v-model="form.game"
                            name="game"
                            class="h-11 w-full cursor-pointer rounded-xl border border-gray-200 bg-gray-100 px-3 text-sm text-gray-900 outline-none transition focus:border-purple-600 focus:bg-white focus:ring-2 focus:ring-purple-100"
                            :disabled="isSubmitting"
                        >
                            <option
                                v-for="game in games"
                                :key="game.key"
                                :value="game.key"
                            >
                                {{ game.name }}
                            </option>
                        </select>
                    </label>

                    <template v-if="form.game === 'minecraft'">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <label class="block sm:col-span-2">
                                <span class="mb-2 block text-sm font-semibold text-gray-700">
                                    Nome do servidor
                                </span>
                                <input
                                    v-model.trim="form.serverName"
                                    type="text"
                                    name="server_name"
                                    maxlength="255"
                                    required
                                    placeholder="Ex.: Survival da Galera"
                                    class="h-11 w-full rounded-xl border bg-gray-100 px-4 text-sm text-gray-900 outline-none transition focus:bg-white focus:ring-2 focus:ring-purple-100"
                                    :class="fieldError('server_name') ? 'border-red-600' : 'border-gray-200 focus:border-purple-600'"
                                    :disabled="isSubmitting"
                                />
                                <span
                                    v-if="fieldError('server_name')"
                                    class="mt-1 block text-xs font-medium text-red-600"
                                >
                                    {{ fieldError('server_name') }}
                                </span>
                            </label>

                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-gray-700">
                                    Versão
                                </span>
                                <select
                                    v-model="form.minecraftVersionId"
                                    name="minecraft_version_id"
                                    required
                                    class="h-11 w-full cursor-pointer rounded-xl border bg-gray-100 px-3 text-sm text-gray-900 outline-none transition focus:bg-white focus:ring-2 focus:ring-purple-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    :class="fieldError('minecraft_version_id') ? 'border-red-600' : 'border-gray-200 focus:border-purple-600'"
                                    :disabled="isSubmitting || isLoadingVersions || Boolean(versionsError)"
                                >
                                    <option value="">
                                        {{ versionPlaceholder }}
                                    </option>
                                    <option
                                        v-for="version in minecraftVersions"
                                        :key="version.id"
                                        :value="String(version.id)"
                                    >
                                        {{ version.version }}
                                    </option>
                                </select>
                                <span
                                    v-if="versionsError"
                                    class="mt-1 block text-xs font-medium text-red-600"
                                >
                                    {{ versionsError }}
                                </span>
                                <span
                                    v-else-if="fieldError('minecraft_version_id')"
                                    class="mt-1 block text-xs font-medium text-red-600"
                                >
                                    {{ fieldError('minecraft_version_id') }}
                                </span>
                            </label>

                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-gray-700">
                                    Dificuldade
                                </span>
                                <select
                                    v-model="form.difficulty"
                                    name="difficulty"
                                    required
                                    class="h-11 w-full cursor-pointer rounded-xl border bg-gray-100 px-3 text-sm text-gray-900 outline-none transition focus:bg-white focus:ring-2 focus:ring-purple-100"
                                    :class="fieldError('difficulty') ? 'border-red-600' : 'border-gray-200 focus:border-purple-600'"
                                    :disabled="isSubmitting"
                                >
                                    <option value="0">Pacífico</option>
                                    <option value="1">Fácil</option>
                                    <option value="2">Normal</option>
                                    <option value="3">Difícil</option>
                                </select>
                                <span
                                    v-if="fieldError('difficulty')"
                                    class="mt-1 block text-xs font-medium text-red-600"
                                >
                                    {{ fieldError('difficulty') }}
                                </span>
                            </label>

                            <label class="block sm:col-span-2">
                                <span class="mb-2 block text-sm font-semibold text-gray-700">
                                    Mensagem do servidor
                                    <span class="font-normal text-gray-500">(opcional)</span>
                                </span>
                                <textarea
                                    v-model.trim="form.motd"
                                    name="motd"
                                    rows="3"
                                    maxlength="255"
                                    placeholder="Mensagem exibida aos jogadores"
                                    class="w-full resize-none rounded-xl border bg-gray-100 px-4 py-3 text-sm text-gray-900 outline-none transition focus:bg-white focus:ring-2 focus:ring-purple-100"
                                    :class="fieldError('motd') ? 'border-red-600' : 'border-gray-200 focus:border-purple-600'"
                                    :disabled="isSubmitting"
                                ></textarea>
                                <span
                                    v-if="fieldError('motd')"
                                    class="mt-1 block text-xs font-medium text-red-600"
                                >
                                    {{ fieldError('motd') }}
                                </span>
                            </label>
                        </div>

                        <fieldset>
                            <legend class="text-sm font-semibold text-gray-700">
                                Regras do servidor
                            </legend>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-gray-100 p-4">
                                    <input
                                        v-model="form.forceGamemode"
                                        type="checkbox"
                                        name="force_gamemode"
                                        class="mt-0.5 h-5 w-5 accent-purple-600"
                                        :disabled="isSubmitting"
                                    />
                                    <span>
                                        <span class="block text-sm font-semibold text-gray-900">
                                            Forçar modo de jogo
                                        </span>
                                        <span class="mt-1 block text-xs text-gray-500">
                                            Mantém o modo definido pelo servidor.
                                        </span>
                                    </span>
                                </label>

                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-gray-100 p-4">
                                    <input
                                        v-model="form.allowFlight"
                                        type="checkbox"
                                        name="allow_flight"
                                        class="mt-0.5 h-5 w-5 accent-purple-600"
                                        :disabled="isSubmitting"
                                    />
                                    <span>
                                        <span class="block text-sm font-semibold text-gray-900">
                                            Permitir voo
                                        </span>
                                        <span class="mt-1 block text-xs text-gray-500">
                                            Autoriza voo para jogadores habilitados.
                                        </span>
                                    </span>
                                </label>
                            </div>
                            <span
                                v-if="fieldError('force_gamemode') || fieldError('allow_flight')"
                                class="mt-2 block text-xs font-medium text-red-600"
                            >
                                {{ fieldError('force_gamemode') || fieldError('allow_flight') }}
                            </span>
                        </fieldset>
                    </template>

                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-gray-300 bg-gray-100 px-6 py-10 text-center"
                    >
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-purple-100">
                            <img
                                :src="selectedGame.icon"
                                :alt="`Ícone do jogo ${selectedGame.name}`"
                                class="h-8 w-8 object-contain"
                            />
                        </div>
                        <h3 class="mt-4 font-bold text-gray-900">
                            Criação ainda não disponível
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            O suporte para {{ selectedGame.name }} ainda não foi implementado.
                        </p>
                    </div>

                    <footer class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="h-11 cursor-pointer rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-600 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isSubmitting"
                            @click="requestClose"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-linear-to-r from-indigo-600 to-purple-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="!selectedGame.canCreate || isSubmitting || isLoadingVersions || Boolean(versionsError)"
                        >
                            <svg
                                v-if="isSubmitting"
                                class="h-4 w-4 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                            </svg>
                            {{ submitLabel }}
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
    reactive,
    ref,
    watch,
} from 'vue';
import {
    createMinecraftServer,
    fetchMinecraftVersions,
} from '../services/serverPageService';

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['close', 'created']);

const games = [
    {
        key: 'minecraft',
        name: 'Minecraft',
        icon: '/imgs/icons/minecraft.png',
        canCreate: true,
    },
    {
        key: 'assetto-corsa',
        name: 'Assetto Corsa',
        icon: '/imgs/icons/assetto corsa.png',
        canCreate: false,
    },
    {
        key: 'terraria',
        name: 'Terraria',
        icon: '/imgs/icons/terraria.png',
        canCreate: false,
    },
];

const gameSelect = ref(null);
const minecraftVersions = ref([]);
const isLoadingVersions = ref(false);
const versionsError = ref('');
const isSubmitting = ref(false);
const generalError = ref('');
const fieldErrors = ref({});
let previousBodyOverflow = '';

const form = reactive({
    game: 'minecraft',
    serverName: '',
    motd: '',
    difficulty: '2',
    minecraftVersionId: '',
    forceGamemode: true,
    allowFlight: false,
});

const selectedGame = computed(() => (
    games.find((game) => game.key === form.game) ?? games[0]
));

const versionPlaceholder = computed(() => {
    if (isLoadingVersions.value) {
        return 'Carregando versões...';
    }

    if (versionsError.value) {
        return 'Versões indisponíveis';
    }

    return 'Selecione uma versão';
});

const submitLabel = computed(() => {
    if (!selectedGame.value.canCreate) {
        return 'Indisponível';
    }

    return isSubmitting.value ? 'Criando servidor...' : 'Criar servidor';
});

const fieldError = (field) => fieldErrors.value[field]?.[0] ?? '';

const resetForm = () => {
    Object.assign(form, {
        game: 'minecraft',
        serverName: '',
        motd: '',
        difficulty: '2',
        minecraftVersionId: '',
        forceGamemode: true,
        allowFlight: false,
    });

    generalError.value = '';
    fieldErrors.value = {};
};

const loadMinecraftVersions = async () => {
    if (minecraftVersions.value.length || isLoadingVersions.value) {
        return;
    }

    isLoadingVersions.value = true;
    versionsError.value = '';

    try {
        const versions = await fetchMinecraftVersions();

        if (!versions.length) {
            versionsError.value = 'Nenhuma versão do Minecraft está disponível.';
            return;
        }

        minecraftVersions.value = versions;
    } catch {
        versionsError.value = 'Não foi possível carregar as versões do Minecraft.';
    } finally {
        isLoadingVersions.value = false;
    }
};

const requestClose = () => {
    if (!isSubmitting.value) {
        emit('close');
    }
};

const submitMinecraft = () => createMinecraftServer({
    _token: document.querySelector('input[name="_token"]')?.value ?? '',
    server_name: form.serverName,
    motd: form.motd || null,
    difficulty: Number(form.difficulty),
    minecraft_version_id: Number(form.minecraftVersionId),
    force_gamemode: form.forceGamemode,
    allow_flight: form.allowFlight,
});

const submitHandlers = {
    minecraft: submitMinecraft,
};

const submit = async () => {
    const submitHandler = submitHandlers[form.game];

    if (!submitHandler || isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;
    generalError.value = '';
    fieldErrors.value = {};

    try {
        await submitHandler();
        emit('created');
    } catch (error) {
        if (error.response?.status === 422) {
            fieldErrors.value = error.response.data.errors ?? {};
            generalError.value = 'Revise os campos destacados antes de continuar.';
        } else if (error.response?.status === 419) {
            generalError.value = 'O token CSRF está ausente ou expirou.';
        } else {
            generalError.value = error.response?.data?.message
                ?? 'Não foi possível criar o servidor.';
        }
    } finally {
        isSubmitting.value = false;
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
            await loadMinecraftVersions();
            await nextTick();
            gameSelect.value?.focus();
            return;
        }

        document.body.style.overflow = previousBodyOverflow;
    },
);

watch(
    () => form.game,
    (game) => {
        generalError.value = '';
        fieldErrors.value = {};

        if (game === 'minecraft') {
            loadMinecraftVersions();
        }
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
