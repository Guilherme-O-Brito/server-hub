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
                aria-labelledby="settings-modal-title"
                class="relative max-h-screen w-full max-w-2xl overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <div
                    class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-indigo-600 to-purple-600"
                    aria-hidden="true"
                ></div>

                <header class="flex items-start justify-between gap-4 border-b border-gray-200 p-6">
                    <div>
                        <h2 id="settings-modal-title" class="text-2xl font-black text-gray-900">
                            Configurações
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Altere as configurações principais do servidor.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl text-gray-500 transition hover:bg-gray-100 hover:text-purple-700"
                        aria-label="Fechar modal"
                        :disabled="isSubmitting"
                        @click="requestClose"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </header>

                <form class="space-y-5 p-6" @submit.prevent="submit">
                    <div
                        v-if="generalError"
                        class="rounded-xl border border-red-200 bg-red-200 p-4 text-sm font-medium text-red-600"
                        role="alert"
                    >
                        {{ generalError }}
                    </div>

                    <div
                        v-if="server?.status !== 'stopped'"
                        class="rounded-xl border border-amber-200 bg-amber-200 p-4 text-sm font-medium text-yellow-600"
                    >
                        O servidor precisa estar parado para salvar alterações.
                    </div>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-gray-700">
                            Nome do servidor
                        </span>
                        <input
                            ref="firstInput"
                            v-model.trim="form.serverName"
                            type="text"
                            name="server_name"
                            maxlength="255"
                            required
                            class="h-11 w-full rounded-xl border bg-gray-100 px-4 text-sm text-gray-900 outline-none transition focus:bg-white focus:ring-2 focus:ring-purple-100"
                            :class="fieldError('server_name') ? 'border-red-600' : 'border-gray-200 focus:border-purple-600'"
                            :disabled="isSubmitting"
                        />
                        <span v-if="fieldError('server_name')" class="mt-1 block text-xs font-medium text-red-600">
                            {{ fieldError('server_name') }}
                        </span>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-gray-700">
                            MOTD
                        </span>
                        <textarea
                            v-model.trim="form.motd"
                            name="motd"
                            rows="3"
                            maxlength="255"
                            class="w-full resize-none rounded-xl border bg-gray-100 px-4 py-3 text-sm text-gray-900 outline-none transition focus:bg-white focus:ring-2 focus:ring-purple-100"
                            :class="fieldError('motd') ? 'border-red-600' : 'border-gray-200 focus:border-purple-600'"
                            :disabled="isSubmitting"
                        ></textarea>
                        <span v-if="fieldError('motd')" class="mt-1 block text-xs font-medium text-red-600">
                            {{ fieldError('motd') }}
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
                            <option value="">{{ versionPlaceholder }}</option>
                            <option
                                v-for="version in versions"
                                :key="version.id"
                                :value="String(version.id)"
                            >
                                {{ version.version }}
                            </option>
                        </select>
                        <span v-if="versionsError" class="mt-1 block text-xs font-medium text-red-600">
                            {{ versionsError }}
                        </span>
                        <span v-else-if="fieldError('minecraft_version_id')" class="mt-1 block text-xs font-medium text-red-600">
                            {{ fieldError('minecraft_version_id') }}
                        </span>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-gray-700">
                            Dificuldade
                        </span>
                        <select
                            v-model.number="form.difficulty"
                            name="difficulty"
                            required
                            class="h-11 w-full cursor-pointer rounded-xl border bg-gray-100 px-3 text-sm text-gray-900 outline-none transition focus:bg-white focus:ring-2 focus:ring-purple-100"
                            :class="fieldError('difficulty') ? 'border-red-600' : 'border-gray-200 focus:border-purple-600'"
                            :disabled="isSubmitting"
                        >
                            <option :value="0">Pacífico</option>
                            <option :value="1">Fácil</option>
                            <option :value="2">Normal</option>
                            <option :value="3">Difícil</option>
                        </select>
                        <span v-if="fieldError('difficulty')" class="mt-1 block text-xs font-medium text-red-600">
                            {{ fieldError('difficulty') }}
                        </span>
                    </label>

                    <div class="rounded-xl border border-amber-200 bg-amber-200 p-4">
                        <p class="text-sm font-bold text-yellow-600">Atenção ao alterar a versão</p>
                        <p class="mt-1 text-xs text-yellow-600">
                            Mudanças de versão podem causar incompatibilidade com mundos, plugins ou mods. Os limitadores de downgrade ainda dependem de regras do backend.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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

                    <footer class="flex justify-between gap-3 border-t border-gray-200 pt-5 sm:flex-row">
                        <button
                            type="button"
                            class="h-11 cursor-pointer rounded-xl bg-red-700 px-5 text-sm font-semibold text-white transition hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="server?.accessRole !== 'owner' || server?.status !== 'stopped' || isSubmitting"
                            :title="server?.accessRole !== 'owner'
                                ? 'Apenas o dono pode excluir o servidor'
                                : server?.status === 'stopped'
                                    ? 'Excluir servidor'
                                    : 'O servidor deve estar parado'"
                            @click="$emit('request-delete')"
                        >
                            Deletar Servidor
                        </button> 
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                           <button
                                type="button"
                                class="h-11 cursor-pointer rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="isSubmitting"
                                @click="requestClose"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="h-11 cursor-pointer rounded-xl bg-purple-700 px-5 text-sm font-semibold text-white transition hover:bg-purple-800 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="server?.status !== 'stopped' || isSubmitting || isLoadingVersions || Boolean(versionsError)"
                            >
                                {{ isSubmitting ? 'Salvando...' : 'Salvar configurações' }}
                            </button> 
                        </div>
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
    fetchMinecraftVersions,
    updateMinecraftServer,
} from '../services/minecraftServerService';

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    server: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'updated', 'request-delete']);

const firstInput = ref(null);
const versions = ref([]);
const versionsError = ref('');
const isLoadingVersions = ref(false);
const isSubmitting = ref(false);
const generalError = ref('');
const fieldErrors = ref({});
let previousBodyOverflow = '';

const form = reactive({
    serverName: '',
    motd: '',
    minecraftVersionId: '',
    difficulty: 2,
    forceGamemode: false,
    allowFlight: false,
});

const versionPlaceholder = computed(() => {
    if (isLoadingVersions.value) {
        return 'Carregando versões...';
    }

    if (versionsError.value) {
        return 'Versões indisponíveis';
    }

    return 'Selecione uma versão';
});

const fieldError = (field) => fieldErrors.value[field]?.[0] ?? '';

const resetForm = () => {
    Object.assign(form, {
        serverName: props.server?.name ?? '',
        motd: props.server?.motd ?? '',
        minecraftVersionId: props.server?.version?.id
            ? String(props.server.version.id)
            : '',
        difficulty: Number(props.server?.difficulty ?? 2),
        forceGamemode: Boolean(props.server?.forceGamemode),
        allowFlight: Boolean(props.server?.allowFlight),
    });

    generalError.value = '';
    fieldErrors.value = {};
};

const loadVersions = async () => {
    if (versions.value.length || isLoadingVersions.value) {
        return;
    }

    isLoadingVersions.value = true;
    versionsError.value = '';

    try {
        const availableVersions = await fetchMinecraftVersions();

        if (!availableVersions.length) {
            versionsError.value = 'Nenhuma versão do Minecraft está disponível.';
            return;
        }

        versions.value = availableVersions;
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

const submit = async () => {
    if (!props.server || props.server.status !== 'stopped' || isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;
    generalError.value = '';
    fieldErrors.value = {};

    try {
        await updateMinecraftServer(props.server.id, {
            server_name: form.serverName,
            motd: form.motd || null,
            difficulty: form.difficulty,
            minecraft_version_id: Number(form.minecraftVersionId),
            force_gamemode: form.forceGamemode,
            allow_flight: form.allowFlight,
        });
        emit('updated');
    } catch (error) {
        if (error.response?.status === 422) {
            fieldErrors.value = error.response.data.errors ?? {};
            generalError.value = 'Revise os campos destacados antes de continuar.';
        } else if (error.response?.status === 403) {
            generalError.value = 'Você não possui permissão para alterar este servidor.';
        } else if (error.response?.status === 409) {
            generalError.value = 'O servidor precisa estar parado para receber alterações.';
        } else {
            generalError.value = error.response?.data?.message
                ?? 'Não foi possível salvar as configurações.';
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
            await loadVersions();
            await nextTick();
            firstInput.value?.focus();
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
