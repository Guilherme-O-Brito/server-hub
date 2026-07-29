<template>
    <article
        class="relative min-h-44 w-72 shrink-0 overflow-hidden rounded-2xl border bg-white p-5 shadow-sm"
        :class="slot.server ? 'border-gray-200' : 'border-dashed border-gray-300'"
    >
        <div
            class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-indigo-600 to-purple-600"
            aria-hidden="true"
        ></div>

        <div class="flex items-center justify-between gap-3">
            <h3 class="font-bold text-gray-900">{{ slot.name }}</h3>
            <StatusBadge :status="slot.status" context="slot" />
        </div>

        <div v-if="slot.server" class="mt-6 flex items-center gap-3">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-100 p-2">
                <img
                    :src="slot.server.game.icon"
                    :alt="`Ícone do jogo ${slot.server.game.name}`"
                    class="h-full w-full object-contain"
                />
            </div>

            <div class="min-w-0">
                <p class="text-sm text-gray-500">{{ slot.server.game.name }}</p>
                <p class="font-semibold text-gray-900">{{ slot.server.name }}</p>
            </div>
        </div>

        <p v-if="slot.server" class="mt-4 truncate text-sm text-gray-500">
            Dono:
            <span class="font-medium text-gray-700">{{ slot.server.owner }}</span>
        </p>

        <div
            v-else
            class="mt-6 h-20 rounded-xl border border-dashed border-gray-200 bg-gray-100"
            aria-hidden="true"
        ></div>
    </article>
</template>

<script setup>
import StatusBadge from './StatusBadge.vue';

defineProps({
    slot: {
        type: Object,
        required: true,
    },
});
</script>
