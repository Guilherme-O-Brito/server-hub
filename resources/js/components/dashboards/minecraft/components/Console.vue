<template>
    <div>
        <h2 class="text-center mb-2 font-bold text-purple-600 text-2xl">Console</h2>
        <div class="bg-white rounded-xl outline-1 text-left text-base">
            <ol ref="consoleContainer" class="m-3 overflow-y-auto h-130">
                <li v-for="message in messages" :key="message.id"> {{ message.text }}</li>
            </ol>
        </div>
        <form @submit.prevent="sendCommand">
                <div class="flex my-2">
                <input 
                    type="text"
                    name="command" 
                    v-model="command"
                    placeholder="Digite um comando"
                    class="w-full px-4 py-2 rounded-lg rounded-r-none bg-gray-100 text-gray-700" />
                <button
                    type="submit"
                    class="flex items-center px-4 py-2 bg-purple-600 rounded-lg text-white cursor-pointer rounded-l-none hover:bg-purple-700 transition duration-400 ease-out">
                <svg class="w-5 mr-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                    <path d="M498.1 5.6c10.1 7 15.4 19.1 13.5 31.2l-64 416c-1.5 9.7-7.4 18.2-16 23s-18.9 5.4-28 1.6L284 427.7l-68.5 74.1c-8.9 9.7-22.9 12.9-35.2 8.1S160 493.2 160 480l0-83.6c0-4 1.5-7.8 4.2-10.8L331.8 202.8c5.8-6.3 5.6-16-.4-22s-15.7-6.4-22-.7L106 360.8 17.7 316.6C7.1 311.3 .3 300.7 0 288.9s5.9-22.8 16.1-28.7l448-256c10.7-6.1 23.9-5.5 34 1.4z"/>
                </svg>
                    <span class="font-semibold">Enviar</span>
                </button>
            </div>
        </form>
    </div>

</template>

<script>

    import { route } from 'ziggy-js';
    import { Ziggy } from '../../../../ziggy.js';

    export default {
        data() {
            return {
                messages: [],
                command: '',
            }
        },

        methods: {

            async sendCommand() {
                try {
                    const response = await axios.post(route('dashboard.minecraft.sendCommand', undefined, undefined, Ziggy), {
                        command: this.command
                    });
                    this.command = ''; // limpando a entrada do usuario
                } catch (e) {
                    console.error(e);
                }
            },

            cleanMessage(message) {

                // remove caracteres ANSI não suportados por navegadores
                let cleanMessage = message.replace(/\x1B(?:[@-Z\\-_]|\[[0-?]*[ -/]*[@-~])/g, '').replace(/^> ?/gm, '');
                // remove caracteres de controle ASCII
                cleanMessage = cleanMessage.replace(/[\x00-\x1F\x7F]/g,'');

                return cleanMessage;
            },

            async initConsoleMessages() {
                try {
                    const response = await axios.get('/api/console/history', {
                        // Envia os cookies da sessão para garantir acesso a api
                        withCredentials: true,
                        withXSRFToken: true 
                    });
                    const data = response.data;

                    data.forEach(message => this.messages.push({id: Date.now(), text: message}));

                } catch (error) {
                    console.log(error);
                }
            },

            // metodo para descer a scroll bar do console para o final sempre que receber nova mensagem do servidor
            scrollDown() {
                this.$nextTick(() => {
                    const container = this.$refs.consoleContainer;
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                });
            },
        },

        mounted() {
            this.initConsoleMessages();
            Echo.private('console-output')
                .listen('ConsoleOutput', (e) => {
                    this.messages.push({id: Date.now(), text: e.message});
                    this.scrollDown();
                });
        },

    }
</script>