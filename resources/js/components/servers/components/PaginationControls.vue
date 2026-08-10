<template>
    <nav class="flex items-center justify-center gap-2" aria-label="Paginação dos servidores">
        <button
            type="button"
            class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 shadow-sm transition hover:border-purple-600 hover:text-purple-600 hover:cursor-pointer disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="currentPage === 1"
            aria-label="Página anterior"
            @click="$emit('change', currentPage - 1)"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6" />
            </svg>
        </button>

        <button
            v-for="page in totalPages"
            :key="page"
            type="button"
            class="h-10 min-w-10 rounded-xl px-3 text-sm font-semibold shadow-sm transition hover:cursor-pointer"
            :class="page === currentPage
                ? 'bg-purple-600 text-white'
                : 'border border-gray-200 bg-white text-gray-700 hover:border-purple-600 hover:text-purple-600'"
            :aria-current="page === currentPage ? 'page' : undefined"
            @click="$emit('change', page)"
        >
            {{ page }}
        </button>

        <button
            type="button"
            class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 shadow-sm transition hover:border-purple-600 hover:text-purple-600 hover:cursor-pointer disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="currentPage === totalPages"
            aria-label="Próxima página"
            @click="$emit('change', currentPage + 1)"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" />
            </svg>
        </button>
    </nav>
</template>

<script setup>
defineProps({
    currentPage: {
        type: Number,
        required: true,
    },
    totalPages: {
        type: Number,
        required: true,
    },
});

defineEmits(['change']);
</script>
