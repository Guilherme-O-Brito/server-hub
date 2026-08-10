<template>
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <header class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Console</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Logs e comandos do servidor em tempo real.
                </p>
            </div>
        </header>

        <div class="bg-gray-900 p-5 font-mono text-sm text-gray-200">
            <div class="flex h-80 items-center justify-center overflow-y-auto rounded-xl border border-gray-700 bg-gray-900">
                <div class="max-w-md px-6 text-center">
                    <svg class="mx-auto h-8 w-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.7"
                            d="m8 9 3 3-3 3m5 0h3M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"
                        />
                    </svg>
                    <p class="mt-3 font-sans text-sm text-gray-400">
                        {{ consoleMessage }}
                    </p>
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <div class="flex h-11 flex-1 items-center rounded-xl border border-gray-700 bg-gray-800 px-4">
                    <span class="mr-3 text-green-500" aria-hidden="true">&gt;</span>
                    <input
                        type="text"
                        name="command"
                        class="w-full bg-transparent text-sm text-gray-300 outline-none placeholder:text-gray-500 disabled:cursor-not-allowed"
                        :placeholder="commandPlaceholder"
                        disabled
                    />
                </div>
                <button
                    type="button"
                    class="h-11 rounded-xl bg-purple-700 px-5 font-sans text-sm font-semibold text-white opacity-40"
                    disabled
                >
                    Enviar
                </button>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import StatusBadge from '../../servers/components/StatusBadge.vue';

const props = defineProps({
    status: {
        type: String,
        default: null,
    },
});

const consoleMessage = computed(() => (
    props.status === 'running'
        ? 'A integração de logs em tempo real ainda não está disponível.'
        : 'O console fica disponível quando o servidor está em execução.'
));

const commandPlaceholder = computed(() => (
    props.status === 'running'
        ? 'Envio de comandos indisponível'
        : 'Servidor fora de execução'
));
</script>
