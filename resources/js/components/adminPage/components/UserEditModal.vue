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
            <div class="flex mb-4">
                <h2 class="font-semibold text-purple-600">Criado em: <span class="font-medium text-gray-800">14/07/2025</span></h2>
                <h2 class="font-semibold text-purple-600">Modificado em: <span class="font-medium text-gray-800">14/07/2025</span></h2>
            </div>
            <form action="" method="post">
                <input hidden name="id" type="text" value="teste">
                <label for="name" class="block mb-2 text-md font-medium text-gray-800">Nome</label>
                <input 
                    id="name"
                    type="text" name="name"
                    placeholder="Digite o nome aqui" 
                    class="bg-gray-200 text-gray-800 text-md rounded-lg focus:outline-purple-300 focus:ring-0 focus:ring-offset-0 block w-full p-2.5">
                
                <label for="email" class="block mb-2 mt-4 text-md font-medium text-gray-800">Email</label>
                <input 
                    id="email"
                    type="text" name="email"
                    placeholder="Digite o email aqui" 
                    class="bg-gray-200 text-gray-800 text-md rounded-lg focus:outline-purple-300 focus:ring-0 focus:ring-offset-0 block w-full p-2.5">
                
                <label for="password" class="block mb-2 mt-4 text-md font-medium text-gray-800">Senha</label>
                <input 
                    id="password"
                    type="text" name="password"
                    placeholder="Digite a senha aqui" 
                    class="bg-gray-200 text-gray-800 text-md rounded-lg focus:outline-purple-300 focus:ring-0 focus:ring-offset-0 block w-full p-2.5">
                
                <div class="flex mt-4">
                    <label for="admin" class="text-md font-medium text-gray-800 mr-4">Admin</label>
                    <input id="admin" type="checkbox" value="" class="form-checkbox text-purple-600 bg-gray-100 border-gray-300 rounded mt-1 focus:outline-none focus:ring-0 focus:ring-offset-0">
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
export default {
    props: {
        show: Boolean,
    },
}
</script>