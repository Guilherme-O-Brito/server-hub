<template>
    <article class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div
            class="absolute inset-y-0 left-0 w-1 bg-linear-to-b from-indigo-600 to-purple-600"
            aria-hidden="true"
        ></div>

        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-500">
                    Jogadores online
                </h2>
                <p class="mt-3 text-3xl font-black text-gray-900">
                    {{ onlinePlayers }}
                    <span class="text-lg font-semibold text-gray-500">
                        / {{ maximumPlayers }}
                    </span>
                </p>
                <p v-if="!players" class="mt-2 text-xs text-gray-500">
                    Dados de jogadores indisponíveis.
                </p>
            </div>

            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M15 19.1a7.5 7.5 0 0 0-6 0m9-2.1a4.5 4.5 0 0 1 3 4.25M6 17a4.5 4.5 0 0 0-3 4.25M12 15.25a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5Zm5.5-7.5a2.5 2.5 0 0 1 0 5M6.5 7.75a2.5 2.5 0 0 0 0 5"
                    />
                </svg>
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    players: {
        type: Object,
        default: null,
    },
});

const onlinePlayers = computed(() => (
    props.players?.online
    ?? props.players?.current
    ?? '—'
));

const maximumPlayers = computed(() => (
    props.players?.max
    ?? props.players?.limit
    ?? '—'
));
</script>
