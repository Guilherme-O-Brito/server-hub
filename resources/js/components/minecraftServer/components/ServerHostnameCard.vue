<template>
    <article class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div
            class="absolute inset-y-0 left-0 w-1 bg-linear-to-b from-indigo-600 to-purple-600"
            aria-hidden="true"
        ></div>

        <div>
            <h2 class="text-lg font-bold text-gray-900">Endereço do servidor</h2>
            <p class="mt-1 text-sm text-gray-500">
                Use este hostname para se conectar ao servidor.
            </p>
        </div>

        <form class="mt-5 flex flex-col gap-3 sm:flex-row" @submit.prevent="copyHostname">
            <label class="min-w-0 flex-1">
                <span class="sr-only">Hostname do servidor</span>
                <input
                    ref="hostnameInput"
                    :value="hostname"
                    type="text"
                    readonly
                    aria-readonly="true"
                    class="h-11 w-full rounded-xl border border-gray-200 bg-gray-100 px-4 font-mono text-sm text-gray-900 outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-100"
                />
            </label>
            <button
                type="submit"
                class="flex h-11 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 text-sm font-semibold text-white transition hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="!hostname"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.25 7.5V6a2.25 2.25 0 0 1 2.25-2.25h6A2.25 2.25 0 0 1 18.75 6v9A2.25 2.25 0 0 1 16.5 17.25H15m-6.75-9h-1.5A2.25 2.25 0 0 0 4.5 10.5v7.5a2.25 2.25 0 0 0 2.25 2.25h5.25A2.25 2.25 0 0 0 14.25 18v-7.5A2.25 2.25 0 0 0 12 8.25H8.25Z" />
                </svg>
                {{ copyStatus === 'copied' ? 'Copiado' : 'Copiar' }}
            </button>
        </form>

        <p v-if="copyStatus === 'error'" class="mt-3 text-xs font-medium text-red-600" role="alert">
            Não foi possível copiar o hostname.
        </p>
    </article>
</template>

<script setup>
import { onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    hostname: {
        type: String,
        default: '',
    },
});

const hostnameInput = ref(null);
const copyStatus = ref('idle');
let resetTimer = null;

const copyWithFallback = () => {
    hostnameInput.value?.select();
    const copied = document.execCommand('copy');
    window.getSelection()?.removeAllRanges();

    if (!copied) {
        throw new Error('Clipboard API unavailable');
    }
};

const copyHostname = async () => {
    if (!props.hostname) {
        return;
    }

    window.clearTimeout(resetTimer);

    try {
        try {
            if (!navigator.clipboard?.writeText) {
                throw new Error('Clipboard API unavailable');
            }

            await navigator.clipboard.writeText(props.hostname);
        } catch {
            copyWithFallback();
        }

        copyStatus.value = 'copied';
        resetTimer = window.setTimeout(() => {
            copyStatus.value = 'idle';
        }, 2000);
    } catch {
        copyStatus.value = 'error';
    }
};

onBeforeUnmount(() => {
    window.clearTimeout(resetTimer);
});
</script>
