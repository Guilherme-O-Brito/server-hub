<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @vite(['resources/css/app.css'])
    <title>Login</title>
</head>
<body class="h-screen w-screen flex items-center justify-center bg-gradient-to-tr from-indigo-600 to-purple-600">
    <div class="">
        <div class="bg-white shadow-xl rounded-md p-20">
            <div class="flex items-center space-x-20">

                <div class="flex-1">
                    <img src="{{ asset('imgs/logo/Logo server-hub2.svg') }}" alt="Logo" class="w-90">
                </div>

                <div class="flex-1 scale-120">
                    <form action="" class="space-y-4">
                        <h2 class="text-2xl font-bold text-center mb-4">Login</h2>
                        <div>
                            <input 
                                id="user"
                                type="text"
                                placeholder="Usuário"
                                class="w-full bg-gray-200 border border-gray-300 text-gray-800 focus:outline-none rounded-3xl p-2 transition duration-200 ease-in-out focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20"
                            >
                        </div>

                        <div>
                            <input 
                                id="password"
                                type="password"
                                placeholder="Senha"
                                class="w-full bg-gray-200 border border-gray-300 text-gray-800 focus:outline-none rounded-3xl p-2 transition duration-200 ease-in-out focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20"
                            >
                        </div>

                        <div class="text-center">
                            <button
                                type="submit"
                                class="flex items-center justify-center gap-2 w-full mt-5 font-bold text-xl text-white bg-gradient-to-bl from-indigo-600 to-purple-600 px-4 py-2 rounded-3xl transition duration-300 ease-in-out hover:bg-gradient-to-tr hover:ring-2 ring-purple-400 hover:scale-110 cursor-pointer"
                            >
                                Entrar
                                <svg class="w-5" data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            
            </div>
        </div>
    </div>
</body>
</html>