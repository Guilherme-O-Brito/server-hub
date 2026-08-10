<template>
    <article
        class="group relative flex min-h-72 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl"
    >
        <div
            class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-indigo-600 to-purple-600"
            aria-hidden="true"
        ></div>

        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-100 p-2">
                    <img
                        :src="server.game.icon"
                        :alt="`Ícone do jogo ${server.game.name}`"
                        class="h-full w-full object-contain"
                    />
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-gray-900">{{ server.name }}</h3>
                    <p class="text-sm text-gray-500">{{ server.game.name }}</p>
                </div>
            </div>

            <span
                class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                :class="accessConfig.classes"
            >
                {{ accessConfig.label }}
            </span>
        </div>

        <div class="mt-6 flex items-center justify-between gap-3">
            <StatusBadge :status="server.status" />

            <div class="flex items-center gap-1.5 text-sm text-gray-700">
                <svg
                    class="h-5 w-5 text-purple-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M15 19.1a7.5 7.5 0 0 0-6 0m9-2.1a4.5 4.5 0 0 1 3 4.25M6 17a4.5 4.5 0 0 0-3 4.25M12 15.25a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5Zm5.5-7.5a2.5 2.5 0 0 1 0 5M6.5 7.75a2.5 2.5 0 0 0 0 5"
                    />
                </svg>
                <span class="font-semibold">{{ server.players.online }}</span>
                <span class="text-gray-500">/ {{ server.players.limit }}</span>
            </div>
        </div>

        <div class="mt-auto pt-6">
            <button
                type="button"
                class="flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 text-sm font-semibold text-white shadow-sm transition duration-300 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2"
                @click="$emit('manage', server)"
            >
                Gerenciar
                <svg
                    class="h-4 w-4 transition group-hover:translate-x-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="m9 18 6-6-6-6"
                    />
                </svg>
            </button>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';
import StatusBadge from './StatusBadge.vue';

const props = defineProps({
    server: {
        type: Object,
        required: true,
    },
});

defineEmits(['manage']);

const accessConfig = computed(() => {
    if (props.server.access === 'owner') {
        return {
            label: 'Proprietário',
            classes: 'bg-indigo-200 text-indigo-600',
        };
    }

    return {
        label: 'Administrador',
        classes: 'bg-purple-100 text-purple-700',
    };
});
</script>
