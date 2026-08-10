<template>
    <article class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div
            class="absolute inset-y-0 left-0 w-1 bg-linear-to-b from-indigo-600 to-purple-600"
            aria-hidden="true"
        ></div>

        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-semibold text-gray-500">
                    Uso de recursos
                </h2>

                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">CPU</dt>
                        <dd class="font-bold text-gray-900">{{ cpuUsage }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">RAM</dt>
                        <dd class="font-bold text-gray-900">{{ memoryUsage }}</dd>
                    </div>
                </dl>

                <p v-if="!resources" class="mt-2 text-xs text-gray-500">
                    Métricas do servidor indisponíveis.
                </p>
            </div>

            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M9 3v2m6-2v2M9 19v2m6-2v2M3 9h2m-2 6h2m14-6h2m-2 6h2M8 6h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Zm2 4h4v4h-4v-4Z"
                    />
                </svg>
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    resources: {
        type: Object,
        default: null,
    },
});

const cpuUsage = computed(() => {
    const value = props.resources?.cpu_usage ?? props.resources?.cpu;

    return value === null || value === undefined ? '—' : `${value}%`;
});

const memoryUsage = computed(() => {
    const used = props.resources?.memory_usage ?? props.resources?.memory_used;
    const total = props.resources?.total_memory ?? props.resources?.memory_total;

    if (used === null || used === undefined) {
        return '—';
    }

    return total === null || total === undefined
        ? `${used} GB`
        : `${used} / ${total} GB`;
});
</script>
