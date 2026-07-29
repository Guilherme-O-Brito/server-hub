<template>
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-4">
            <label class="relative block lg:col-span-2">
                <span class="mb-2 block text-sm font-semibold text-gray-700">
                    Buscar servidor
                </span>
                <span
                    class="pointer-events-none absolute bottom-3 left-3 text-gray-500"
                    aria-hidden="true"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                        />
                    </svg>
                </span>
                <input
                    type="search"
                    :value="search"
                    placeholder="Pesquisar por nome..."
                    class="h-11 w-full rounded-xl border border-gray-200 bg-gray-100 py-2 pr-4 pl-10 text-sm text-gray-900 outline-none transition focus:border-purple-600 focus:bg-white focus:ring-2 focus:ring-purple-100"
                    @input="$emit('update:search', $event.target.value)"
                />
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-gray-700">
                    Jogo
                </span>
                <select
                    :value="game"
                    class="h-11 w-full cursor-pointer rounded-xl border border-gray-200 bg-gray-100 px-3 text-sm text-gray-900 outline-none transition focus:border-purple-600 focus:bg-white focus:ring-2 focus:ring-purple-100"
                    @change="$emit('update:game', $event.target.value)"
                >
                    <option value="all">Todos os jogos</option>
                    <option
                        v-for="gameOption in games"
                        :key="gameOption.key"
                        :value="gameOption.key"
                    >
                        {{ gameOption.name }}
                    </option>
                </select>
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-gray-700">
                    Meu acesso
                </span>
                <select
                    :value="access"
                    class="h-11 w-full cursor-pointer rounded-xl border border-gray-200 bg-gray-100 px-3 text-sm text-gray-900 outline-none transition focus:border-purple-600 focus:bg-white focus:ring-2 focus:ring-purple-100"
                    @change="$emit('update:access', $event.target.value)"
                >
                    <option value="all">Todos</option>
                    <option value="owner">Meus servidores</option>
                    <option value="admin">Sou administrador</option>
                </select>
            </label>
        </div>
    </div>
</template>

<script setup>
defineProps({
    search: {
        type: String,
        required: true,
    },
    game: {
        type: String,
        required: true,
    },
    access: {
        type: String,
        required: true,
    },
    games: {
        type: Array,
        required: true,
    },
});

defineEmits([
    'update:search',
    'update:game',
    'update:access',
]);
</script>
