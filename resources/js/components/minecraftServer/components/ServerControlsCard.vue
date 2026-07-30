<template>
    <article class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div
            class="absolute inset-y-0 left-0 w-1 bg-linear-to-b from-indigo-600 to-purple-600"
            aria-hidden="true"
        ></div>

        <div class="flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-500">
                Controle do servidor
            </h2>
            <StatusBadge :status="status ?? 'unknown'" />
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3">
            <button
                type="button"
                class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-green-600 px-4 text-sm font-semibold text-white transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="busy || status !== 'stopped'"
                @click="$emit('start')"
            >
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 4.8v14.4c0 1.1 1.2 1.8 2.2 1.2l11.1-7.2c.9-.6.9-1.9 0-2.4L9.2 3.6C8.2 3 7 3.7 7 4.8Z" />
                </svg>
                Iniciar
            </button>

            <button
                type="button"
                class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-red-600 px-4 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="busy || status !== 'running'"
                @click="$emit('stop')"
            >
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6.5 5h11A1.5 1.5 0 0 1 19 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 17.5v-11A1.5 1.5 0 0 1 6.5 5Z" />
                </svg>
                Parar
            </button>
        </div>

        <p v-if="error" class="mt-3 text-xs font-medium text-red-600" role="alert">
            {{ error }}
        </p>
    </article>
</template>

<script setup>
import StatusBadge from '../../servers/components/StatusBadge.vue';

defineProps({
    status: {
        type: String,
        default: null,
    },
    busy: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
});

defineEmits(['start', 'stop']);
</script>
