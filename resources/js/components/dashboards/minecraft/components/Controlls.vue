<template>
    
    <div class="bg-white rounded-xl shadow p-6">
        <div class="inline-flex justify-between w-full">
            <h2 class="text-lg font-semibold text-purple-600">Controles</h2>
            <div 
            :class="[
                'flex', 
                'items-center', 
                'space-x-1', 
                'w-min', 
                'text-right', 
                'font-bold', 
                'text-sm', 
                'px-2 rounded-3xl', 
                {'text-red-600': isOffline(), 'bg-red-200': isOffline(), 'text-yellow-600': isStarting() || isTurningOff(), 'bg-amber-200': isStarting() || isTurningOff(), 'text-green-600': isOnline(), 'bg-green-200': isOnline(),}
            ]">
                <svg class="w-4" data-slot="icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M21.721 12.752a9.711 9.711 0 0 0-.945-5.003 12.754 12.754 0 0 1-4.339 2.708 18.991 18.991 0 0 1-.214 4.772 17.165 17.165 0 0 0 5.498-2.477ZM14.634 15.55a17.324 17.324 0 0 0 .332-4.647c-.952.227-1.945.347-2.966.347-1.021 0-2.014-.12-2.966-.347a17.515 17.515 0 0 0 .332 4.647 17.385 17.385 0 0 0 5.268 0ZM9.772 17.119a18.963 18.963 0 0 0 4.456 0A17.182 17.182 0 0 1 12 21.724a17.18 17.18 0 0 1-2.228-4.605ZM7.777 15.23a18.87 18.87 0 0 1-.214-4.774 12.753 12.753 0 0 1-4.34-2.708 9.711 9.711 0 0 0-.944 5.004 17.165 17.165 0 0 0 5.498 2.477ZM21.356 14.752a9.765 9.765 0 0 1-7.478 6.817 18.64 18.64 0 0 0 1.988-4.718 18.627 18.627 0 0 0 5.49-2.098ZM2.644 14.752c1.682.971 3.53 1.688 5.49 2.099a18.64 18.64 0 0 0 1.988 4.718 9.765 9.765 0 0 1-7.478-6.816ZM13.878 2.43a9.755 9.755 0 0 1 6.116 3.986 11.267 11.267 0 0 1-3.746 2.504 18.63 18.63 0 0 0-2.37-6.49ZM12 2.276a17.152 17.152 0 0 1 2.805 7.121c-.897.23-1.837.353-2.805.353-.968 0-1.908-.122-2.805-.353A17.151 17.151 0 0 1 12 2.276ZM10.122 2.43a18.629 18.629 0 0 0-2.37 6.49 11.266 11.266 0 0 1-3.746-2.504 9.754 9.754 0 0 1 6.116-3.985Z"></path>
                </svg>
                <div>{{ serverState }}</div>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4 text-purple-600 items-center justify-between h-full">
            <button 
                @click="startServer"
                :disabled="!isOffline()"
                title="Iniciar Servidor" 
                class="flex justify-center items-center hover:bg-purple-100 rounded-4xl cursor-pointer transition duration-400 ease-out disabled:opacity-50"
            >
                <svg class="w-10" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. -->
                    <path d="M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80L0 432c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z"/>
                </svg>
            </button>
            <button 
                @click="stopServer"
                :disabled="!isOnline()"
                title="Desligar Servidor" 
                class="flex justify-center items-center hover:bg-purple-100 rounded-4xl cursor-pointer transition duration-400 ease-out disabled:opacity-50"
            >
                <svg class="w-10" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. -->
                    <path d="M0 128C0 92.7 28.7 64 64 64H320c35.3 0 64 28.7 64 64V384c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128z"/>
                </svg>
            </button>
            <button 
            :disabled="isOffline()"
            title="Reiniciar Servidor" 
            class="flex justify-center items-center hover:bg-purple-100 rounded-4xl cursor-pointer transition duration-400 ease-out disabled:opacity-50">
                <svg class="w-15" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. -->
                    <path d="M272 416c17.7 0 32-14.3 32-32s-14.3-32-32-32l-112 0c-17.7 0-32-14.3-32-32l0-128 32 0c12.9 0 24.6-7.8 29.6-19.8s2.2-25.7-6.9-34.9l-64-64c-12.5-12.5-32.8-12.5-45.3 0l-64 64c-9.2 9.2-11.9 22.9-6.9 34.9s16.6 19.8 29.6 19.8l32 0 0 128c0 53 43 96 96 96l112 0zM304 96c-17.7 0-32 14.3-32 32s14.3 32 32 32l112 0c17.7 0 32 14.3 32 32l0 128-32 0c-12.9 0-24.6 7.8-29.6 19.8s-2.2 25.7 6.9 34.9l64 64c12.5 12.5 32.8 12.5 45.3 0l64-64c9.2-9.2 11.9-22.9 6.9-34.9s-16.6-19.8-29.6-19.8l-32 0 0-128c0-53-43-96-96-96L304 96z"/>
                </svg>
            </button>
        </div>
    </div>
   
</template>

<script>

export default {

    props: {
        apiData: {},
    },

    data() {
        return {
            serverState: "Offline", // Iniciando o estado do botão
        }
    },

    methods: {

        isOnline() {
            return this.serverState === "Online";
        },

        isStarting() {
            return this.serverState === "Iniciando";
        },

        isTurningOff() {
            return this.serverState === "Desligando";
        },

        isOffline() {
            return this.serverState === "Offline";
        },

        async startServer() {

            if (this.serverState === "Iniciando") return; // evita varias requisições 

            try {
                const response = await axios.post("./minecraft/start");

                if (response.status === 200) {
                    this.serverState = "Iniciando"; // mudando status para iniciando
                } else {
                    console.error("Houve alguma falha ao comunicar com o servidor!");
                }

            } catch (error) {
                console.error("Erro ao iniciar servidor: ",error);
            }

        },

        async stopServer() {

            if (this.serverState === "Desligado") return;

            try {
                const response = await axios.post("./minecraft/stop");

                if (response.status != 200) {
                    console.error("Houve alguma falha ao comunicar com o servidor!");
                    return;
                } 

                this.serverState = "Desligando";

            } catch (error) {
                console.error("Erro ao desligar servidor: ",error);
            }

        },

    },

    watch: {

        apiData(newData, oldData) {

            if (newData.online && this.serverState != "Desligando") {
                this.serverState = "Online";
            } else if (!newData.online && this.serverState != "Iniciando") {
                this.serverState = "Offline";
            }

        },

    },

}
</script>