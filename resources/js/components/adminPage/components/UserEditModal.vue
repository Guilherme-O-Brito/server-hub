<template>
<!--Modal Area-->
<div v-if="show" class="fixed inset-0 flex items-center justify-center z-50">
    <div class="bg-white p-4 rounded-lg shadow-sm w-full max-w-md">
        <!--Header-->
        <div class="flex items-center justify-between p-4 border-b rounded-b-none">
            <h3 class="text-2xl font-semibold text-purple-600">Usuário</h3>
            <button @click="$emit('closeUserModal')" type="button" class="text-gray-400 bg-transparent hover:bg-purple-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="crud-modal">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
            </button>
        </div>
        <!--body-->
        <div class="p-4">
            <div class="flex mb-4 justify-between">
                <h2 v-if="created_at" class="font-semibold text-purple-600">Criado em: <span class="font-medium text-gray-800">{{ created_at }}</span></h2>
                <h2 v-if="updated_at" class="font-semibold text-purple-600">Modificado em: <span class="font-medium text-gray-800">{{ updated_at }}</span></h2>
            </div>
            <form @submit.prevent="submiteForm">
                <label for="name" class="block mb-2 text-md font-medium text-gray-800">Nome</label>
                <input 
                    v-model="form.name"
                    id="name"
                    type="text" name="name"
                    placeholder="Digite o nome aqui" 
                    class="bg-gray-200 text-gray-800 text-md rounded-lg focus:outline-purple-300 focus:ring-0 focus:ring-offset-0 block w-full p-2.5">
                <p v-if="errors.name" class="text-red-500 text-sm">{{ errors.name[0] }}</p>
                <label for="email" class="block mb-2 mt-4 text-md font-medium text-gray-800">Email</label>
                <input 
                    id="email"
                    v-model="form.email"
                    type="text" name="email"
                    placeholder="Digite o email aqui" 
                    class="bg-gray-200 text-gray-800 text-md rounded-lg focus:outline-purple-300 focus:ring-0 focus:ring-offset-0 block w-full p-2.5">
                <p v-if="errors.email" class="text-red-500 text-sm">{{ errors.email[0] }}</p>
                <label for="password" class="block mb-2 mt-4 text-md font-medium text-gray-800">Senha</label>
                <input 
                    id="password"
                    v-model="form.password"
                    type="text" name="password"
                    placeholder="Digite a senha aqui" 
                    class="bg-gray-200 text-gray-800 text-md rounded-lg focus:outline-purple-300 focus:ring-0 focus:ring-offset-0 block w-full p-2.5">
                <p v-if="errors.password" class="text-red-500 text-sm">{{ errors.password[0] }}</p>
                <div class="flex mt-4">
                    <label for="is_admin" class="text-md font-medium text-gray-800 mr-4">Admin</label>
                    <input id="is_admin" type="checkbox" v-model="form.is_admin" class="form-checkbox text-purple-600 bg-gray-100 border-gray-300 rounded mt-1 focus:outline-none focus:ring-0 focus:ring-offset-0">
                </div>

                <button type="submit" class="w-full mt-4 bg-purple-600 text-white text-lg font-semibold rounded-lg cursor-pointer hover:bg-purple-700 transition duration-400 ease-out">
                    Salvar
                </button>
            </form>
        </div>
    </div>
</div>

</template>

<script>
import { route } from 'ziggy-js';
import { Ziggy } from '../../../ziggy';
export default {
    props: {
        show: Boolean,
        user: {}
    },
    data() {
        return {
            created_at: '',
            updated_at: '',
            form: {
                name: '',
                email: '',
                password:'',
                is_admin: false
            },
            errors: {}
        }
    },
    watch: {
        user: {
            handler(newValue) {
                if (newValue && newValue.data) {
                    this.form.name = newValue.data[0];
                    this.form.email = newValue.data[1];
                    this.form.is_admin = newValue.data[2];
                    this.created_at = new Date(newValue.created_at).toLocaleDateString();
                    this.updated_at = new Date(newValue.updated_at).toLocaleDateString();
                } else {
                    this.form.name = '';
                    this.form.email = '';
                    this.form.is_admin = false;
                    this.created_at = '';
                    this.updated_at = '';
                }
            }
        },
    },
    methods: {
        async submiteForm() {
            this.errors = {} // limpando erros
            // se o usuario não for nulo então estamos editando um user e não criando um novo
            let url;
            let id;
            if (this.user != null) {
                url = 'updateUser';
                id = this.user.id;
            } else {
                url = 'createUser';
                id = undefined;
            }
            try {
                await axios.post(route(url, id, undefined, Ziggy), this.form)
                .then(response => {
                    location.reload();
                });
                // resetando formulario
                this.form = {
                    name: '',
                    email: '',
                    password:'',
                    is_admin: false
                };
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors;
                } else {
                    console.error(error);
                }
            }

        },
    }
}

</script>