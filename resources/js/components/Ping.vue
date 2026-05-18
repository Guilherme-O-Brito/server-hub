<template>
    
<div class="flex items-center justify-center my-4">
    <div class="flex bg-white rounded-md shadow gap-2 px-4 py-2">
        <div class="text-gray-900 font-semibold">Ping Medio: {{ ping }}ms</div>
        <div>
            <svg :class="{
                'w-5 text-green-500': ping <= 40,
                'w-5 text-yellow-500': ping > 40 && ping <= 80,
                'w-5 text-red-500': ping > 80
                }" data-slot="icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75ZM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 0 1-1.875-1.875V8.625ZM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 0 1 3 19.875v-6.75Z"></path>
            </svg>
        </div>
    </div>
</div>
    
</template>

<script>
export default {

    data() {
        return {
            ping: 0,
        }
    },

    methods: {
        async pingServer() {
            const start = performance.now();
            try {
                await fetch('http://localhost/ping', {method: 'HEAD', cache: 'no-cache'});
                const end = performance.now();
                const latency = end - start;
                return latency.toFixed(0);
            } catch (error) {
                console.log('Erro ao pingar o servidor');
                return 0
            }
        },

        async getPing() {
            setInterval(async () => {
                this.ping = await this.pingServer();
            }, 10000);
        },

    },

    async mounted() {
        this.ping = await this.pingServer();
        this.getPing();
    }

}
</script>