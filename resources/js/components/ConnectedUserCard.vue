<template>
    <article class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div
            class="absolute inset-y-0 left-0 w-1 bg-linear-to-b from-indigo-600 to-purple-600"
            aria-hidden="true"
        ></div>
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-semibold text-gray-500">
                    Conectado como:
                </h2>
                <h1 class="font-bold text-gray-900">{{ user?.name }}</h1>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
            </div>
        </div>
    </article>
</template>

<script setup>
    const props = defineProps({
        user: {
            type: Object,
            default: null
        }
    });
</script>