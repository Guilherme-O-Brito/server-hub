<template>
    <article class="flex min-h-72 flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <header class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900">{{ title }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ description }}</p>
            </div>

            <button
                type="button"
                class="flex h-10 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-xl bg-purple-700 px-3 text-sm font-semibold text-white transition hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="!canAdd || loading"
                :title="canAdd ? `Adicionar em ${title}` : disabledReason"
                @click="$emit('add')"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                </svg>
                Adicionar
            </button>
        </header>

        <div class="mt-5 max-h-48 flex-1 overflow-y-auto rounded-xl border border-gray-200 bg-gray-100">
            <div v-if="loading" class="space-y-2 p-4" aria-label="Carregando itens">
                <div class="h-9 animate-pulse rounded-lg bg-gray-200"></div>
                <div class="h-9 animate-pulse rounded-lg bg-gray-200"></div>
            </div>

            <ul v-else-if="entries.length" class="divide-y divide-gray-200">
                <li
                    v-for="entry in entries"
                    :key="entry.id"
                    class="flex justify-between gap-3 px-4 py-3"
                >   
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0"
                                />
                            </svg>
                        </div>
                        <span class="truncate text-sm font-semibold text-gray-900">
                            {{ entry[entryLabelKey] }}
                        </span>
                    </div>
                    <button 
                        type="button"
                        class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg bg-red-700 text-sm font-semibold text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="!canDelete || loading"
                        :title="canDelete ? `Excluir ${entry[entryLabelKey]}` : disabledReason"
                        :aria-label="`Excluir ${entry[entryLabelKey]} de ${title}`"
                        @click="$emit('delete-entry', entry)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                        </svg>
                    </button>
                </li>
            </ul>

            <p v-else class="px-4 py-10 text-center text-sm text-gray-500">
                {{ emptyMessage }}
            </p>
        </div>

        <p v-if="error" class="mt-3 text-xs font-medium text-red-600" role="alert">
            {{ error }}
        </p>
    </article>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    entries: {
        type: Array,
        default: () => [],
    },
    entryLabelKey: {
        type: String,
        default: 'nickname',
    },
    emptyMessage: {
        type: String,
        default: 'Nenhum nickname cadastrado.',
    },
    canAdd: {
        type: Boolean,
        default: false,
    },
    canDelete: {
        type: Boolean,
        default: false,
    },
    disabledReason: {
        type: String,
        default: 'O servidor deve estar parado',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
});

defineEmits(['add', 'delete-entry']);
</script>
