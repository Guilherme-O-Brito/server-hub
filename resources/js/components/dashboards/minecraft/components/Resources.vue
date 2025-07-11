<template>
    
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex justify-between">
            <div>
                <h2 class="text-lg font-semibold mb-2 text-purple-600">Uso de Recursos</h2>
                <p class="text-sm text-gray-700">CPU: <span class="font-bold text-purple-700">{{ cpuUsage }}%</span></p>
                <p class="text-sm text-gray-700">RAM: <span class="font-bold text-purple-700">{{ memoryUsage }} GB / {{ totalMemory }} GB</span></p>
                <p class="text-sm text-gray-700">TPS: <span class="font-bold text-gray-600">Temporariamente Inoperante</span></p>
            </div>
            <svg class="w-15 text-purple-600" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. -->
                <path d="M176 24c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 40c-35.3 0-64 28.7-64 64l-40 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l40 0 0 56-40 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l40 0 0 56-40 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l40 0c0 35.3 28.7 64 64 64l0 40c0 13.3 10.7 24 24 24s24-10.7 24-24l0-40 56 0 0 40c0 13.3 10.7 24 24 24s24-10.7 24-24l0-40 56 0 0 40c0 13.3 10.7 24 24 24s24-10.7 24-24l0-40c35.3 0 64-28.7 64-64l40 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-40 0 0-56 40 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-40 0 0-56 40 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-40 0c0-35.3-28.7-64-64-64l0-40c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 40-56 0 0-40c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 40-56 0 0-40zM160 128l192 0c17.7 0 32 14.3 32 32l0 192c0 17.7-14.3 32-32 32l-192 0c-17.7 0-32-14.3-32-32l0-192c0-17.7 14.3-32 32-32zm192 32l-192 0 0 192 192 0 0-192z"/>
            </svg>
        </div>
    </div>
    
</template>

<script>

export default {

    data() {
        return {
            cpuUsage: 0,
            totalMemory: 0,
            memoryUsage: 0,
        }
    },

    methods: {
        async initData() {
            try {
                const response = await axios.get('/api/server-status', {
                    // Envia os cookies da sessão para garantir acesso a api
                    withCredentials: true,
                    withXSRFToken: true 
                });
                const data = response.data;
                
                this.cpuUsage = data.cpu_usage;
                this.totalMemory = data.total_memory;
                this.memoryUsage = data.memory_usage;

            } catch (error) {
                console.log(error);
            }
        },

        async getStatus() {
            setInterval(async () => {
                try {
                    const response = await axios.get('/api/server-status', {
                    // Envia os cookies da sessão para garantir acesso a api
                    withCredentials: true,
                    withXSRFToken: true 
                });
                const data = response.data;
                
                this.cpuUsage = data.cpu_usage;
                this.totalMemory = data.total_memory;
                this.memoryUsage = data.memory_usage;

                } catch (error) {
                    console.log(error);
                }
            }, 10000);
        },
    },

    mounted() {
        this.initData();
        this.getStatus();
    },

    
}
</script>