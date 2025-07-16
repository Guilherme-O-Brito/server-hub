<template>
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-4xl font-semibold mb-2 text-purple-600">{{ headTitle }}</h2>
        <div class="flex">
            <input type="text" 
                class="w-full px-4 py-2 rounded-lg rounded-r-none bg-gray-200 text-gray-700" />
            
            <button title="Buscar" class="flex items-center px-4 py-2 bg-purple-600 rounded-lg text-white cursor-pointer rounded-l-none hover:bg-purple-700 transition duration-400 ease-out">
                <svg class="w-5 mr-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. -->
                    <path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/>
                </svg>
                <span class="font-semibold">Buscar</span>
            </button>
            <button @click="$emit('showEditModal')" title="Criar" class="ml-3 px-4 py-2 bg-green-600 rounded-lg text-white cursor-pointer hover:bg-green-700 transition duration-400 ease-out">
                <svg class="w-5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. -->
                    <path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 144L48 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l144 0 0 144c0 17.7 14.3 32 32 32s32-14.3 32-32l0-144 144 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-144 0 0-144z"/>
                </svg>
            </button>
        </div>
        <div class="mt-3">
            <table class="w-full table-auto border border-spacing-2 bg-purple-500">
                <thead class="font-semibold">
                    <tr>
                        <th v-for="column in tHead" :key="column"> {{ column }}</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-purple-300 text-gray-800 text-center">
                    <tr v-for="row in tRow" :key="row.id">
                        <td v-for="(item, index) in row.data" :key="row.id + '-' + index" class="border">{{ item }}</td>
                        <td class="border">
                            <div class="grid grid-cols-2 justify-between my-1">
                                <button
                                    @click="$emit('showEditModal')"
                                    title="Visualisar"
                                    class="bg-blue-700 cursor-pointer flex justify-center items-center hover:bg-blue-800 transition duration-400 ease-out rounded-lg mx-1 p-1">
                                    <svg class="text-white w-3" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. -->
                                        <path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/>
                                    </svg>
                                </button>
                                <button 
                                    @click="$emit('showDeleteModal')"
                                    title="Excluir"
                                    class="bg-red-700 cursor-pointer flex justify-center items-center hover:bg-red-800 transition duration-400 ease-out rounded-lg mx-1 p-1">
                                    <svg class="text-white w-3" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. -->
                                        <path d="M135.2 17.7C140.6 6.8 151.7 0 163.8 0L284.2 0c12.1 0 23.2 6.8 28.6 17.7L320 32l96 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 96C14.3 96 0 81.7 0 64S14.3 32 32 32l96 0 7.2-14.3zM32 128l384 0 0 320c0 35.3-28.7 64-64 64L96 512c-35.3 0-64-28.7-64-64l0-320zm96 64c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</template>

<script>

export default {

    props: {
        tHead: Array,
        tRow: Array,
        headTitle: '',
    },

    data() {
        return {
            
        }
    },
}
</script>