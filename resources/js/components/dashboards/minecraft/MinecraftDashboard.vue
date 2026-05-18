<template>
    <!-- Cards de informações -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
    <!-- Card de Jogadores online -->
    <OnlineUsers :apiData="apiData"/>
    <!-- Card de Jogadores online -->
    <!-- Card de uso de recursos -->
    <Resources />
    <!-- Card de uso de recursos -->
    <!-- caixa de botões -->
    <Controlls :apiData="apiData"/>
    <!-- caixa de botões -->
</div>
    
<!-- pings -->
<Ping />
<!-- pings -->  
    
<!-- last card -->
<div class="bg-white rounded-xl shadow p-6">
    <div class="flex mb-2">
        <label class="block text-purple-600 text-base font-semibold">IP do servidor</label>
        <input type="text" 
                v-model="inputValue" 
                readonly
                class="w-full px-4 py-2 rounded-lg rounded-r-none bg-gray-100 text-gray-700" />
        <button
            @click="copyAdress"
            class="flex items-center px-4 py-2 bg-purple-600 rounded-lg text-white cursor-pointer rounded-l-none hover:bg-purple-700 transition duration-400 ease-out">
            <svg class="w-5 mr-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. -->
                <path d="M208 0L332.1 0c12.7 0 24.9 5.1 33.9 14.1l67.9 67.9c9 9 14.1 21.2 14.1 33.9L448 336c0 26.5-21.5 48-48 48l-192 0c-26.5 0-48-21.5-48-48l0-288c0-26.5 21.5-48 48-48zM48 128l80 0 0 64-64 0 0 256 192 0 0-32 64 0 0 48c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 176c0-26.5 21.5-48 48-48z"/>
            </svg>
            <span class="font-semibold">Copiar</span>
        </button>
    </div>
    <Console />
</div>
<!-- last card -->
</template>

<script>
import Ping from '../../Ping.vue';
import Console from './components/Console.vue';
import Controlls from './components/Controlls.vue';
import OnlineUsers from './components/OnlineUsers.vue';
import Resources from './components/Resources.vue';

export default {

    components: {
        Controlls,
        Resources,
        OnlineUsers,
        Ping,
        Console
    },

    data() {
        return {
            apiData: null,
            inputValue: 'gbrito9000.ddns.net',
        }
    },

    methods: {
        async initData() {
            try {
                /*const response = await fetch(`https://api.mcsrvstat.us/3/gbrito9000.ddns.net`);
                const data = await response.json();

                this.apiData = data;*/

            } catch (error) {
                console.log(error);
            }
        },

        async getStatus() {
            setInterval(async () => {
                try {
                    /*const response = await fetch(`https://api.mcsrvstat.us/3/gbrito9000.ddns.net`, {cache: 'no-cache'});
                    const data = await response.json();
                    
                    this.apiData = data;*/

                } catch (error) {
                    console.log(error);
                }
            }, 20000);
        },

        copyAdress() {
            navigator.clipboard.writeText(this.inputValue);
        },  

    },

    computed: {

    },

    mounted() {
        this.initData();
        this.getStatus();
    },

}



</script>