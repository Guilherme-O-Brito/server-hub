<template>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 m-6">
    <CrudCard head-title="Usuários" :t-head="tHeadUsers" :t-row="tRowUsers" @showEditModal="showUserModal = true" @showDeleteModal="showDeleteModal = true"/>
    <CrudCard head-title="Servidores de Jogos" :t-head="tHeadServers" :t-row="tRowServers"/>
    <Informations />
</div>

<UserEditModal :show="showUserModal" @closeUserModal="showUserModal = false"/>
<DeleteModal :show="showDeleteModal" @closeDeleteModal="showDeleteModal = false"/>

</template>

<script>
import UserEditModal from './components/UserEditModal.vue';
import ServerEditModal from './components/ServerEditModal.vue';
import DeleteModal from './components/DeleteModal.vue';
import CrudCard from './components/CrudCard.vue';
import Informations from './components/Informations.vue';

export default {
    
    props: {
        users: {},
        servers: {}
    },

    data() {
        return {
            tHeadServers: ['Nome', 'Jogo', 'Dono'],
            tRowServers: [],
            tHeadUsers: ['Nome', 'Email', 'Admin'],
            tRowUsers: [],
            showUserModal: false,
            showServerModal: false,
            showDeleteModal: false
        }
    },

    components: {
        CrudCard,
        Informations,
        UserEditModal,
        ServerEditModal,
        DeleteModal
    },

    mounted() {
        // tratando os dados recebidos do servidor e enviando salvando para envio aos components
        this.users.forEach(element => {
            this.tRowUsers.push({id: Number(element['id']), data: [element['name'], element['email'], Boolean(element['is_admin'])]});
        });
        this.servers.forEach(element => {
            this.tRowServers.push({id: Number(element['id']), data: [element['name'], this.getGameName(Number(element['game_type'])), element['owner'].name]});
        });
    },

    methods: {
        getGameName(num) {
            switch (num) {
                case 0:
                    return 'Minecraft'; 
                case 1:
                    return 'Assetto Corsa';
                case 2:
                    return 'Terraria';
            
            }
        }
    },

}
</script>