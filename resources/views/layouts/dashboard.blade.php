<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @vite(['resources/css/app.css'])
    <title>Server Hub</title>
</head>
<body class="bg-gray-100">
    <!-- header -->
    <div>
        <div class="mx-auto px-4 h-24 flex items-center bg-white">
            <div class="font-black">
                <a href="{{ route('home') }}"><img src="{{ asset('imgs/logo/server-hub-logo2.svg') }}" alt="Logo" class="w-70"></a>
            </div>

            <ul class="flex items-center ml-auto space-x-3">
                @auth
                @if (Auth::user()->isAdmin())    
                <li>
                    <a href="{{ route('admin') }}" class="p-2 font-bold text-base text-gray-900 hover:text-purple-800 hover:bg-gray-200 rounded-lg transition duration-400 ease-in-out">Painel Administrativo</a>
                </li>
                @endif
                @endauth
                <li>
                    <a href="{{ route('servidores') }}" class="p-2 font-bold text-base text-gray-900 hover:text-purple-800 hover:bg-gray-200 rounded-lg transition duration-400 ease-in-out">Servidores</a>
                </li>
                <li>
                    <a href="{{ route('sobre') }}" class="p-2 font-bold text-base text-gray-900 hover:text-purple-800 hover:bg-gray-200 rounded-lg transition duration-400 ease-in-out">Sobre</a>
                </li>
                @auth
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="p-2 font-bold text-base text-red-600 hover:bg-red-200 rounded-lg transition duration-400 ease-in-out cursor-pointer">Desconectar</button>
                        </form>
                    </li>
                @endauth
            </ul>

        </div>
    </div>
    <!-- header -->
    <!-- banner -->
    <div class="h-48 bg-cover" style="background: url('{{ asset('imgs/banner/Banner.jpg') }}'); background-position: center center;">
        <div class="container mx-auto"></div>
    </div>
    <!-- banner -->
    <!-- limite de conteudo -->
    <div class="flex min-h-screen text-gray-900">

        <!-- Sidebar lateral esquerda -->
        <aside class="w-64 bg-white shadow-lg flex flex-col">
            <div class="h-16 flex items-center justify-center">
                <span class="text-xl font-bold text-purple-600">ServerHub</span>
            </div>

            <!-- Navegação -->
            <nav class="flex-1 p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('dashboard.assettoCorsa.index') }}" class="flex justify-start items-center text-gray-700 font-medium {{ request()->routeIs('dashboard.assettoCorsa.*') ? "bg-purple-100" : "bg-gray-100" }} p-2 rounded-md cursor-pointer hover:bg-purple-100">
                            <img class="w-9 scale-190" src="{{ asset('imgs/icons/assetto corsa.png') }}" alt="icon">
                            Assetto Corsa
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.minecraft.index') }}" class="flex justify-start items-center text-gray-700 font-medium {{ request()->routeIs('dashboard.minecraft.*') ? "bg-purple-100" : "bg-gray-100" }} p-2 rounded-md cursor-pointer hover:bg-purple-100">
                            <img class="w-9" src="{{ asset('imgs/icons/minecraft.png') }}" alt="icon">
                            Minecraft
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.terraria.index') }}" class="flex justify-start items-center text-gray-700 font-medium {{ request()->routeIs('dashboard.terraria.*') ? "bg-purple-100" : "bg-gray-100" }} p-2 rounded-md cursor-pointer hover:bg-purple-100">
                            <img class="w-9" src="{{ asset('imgs/icons/terraria.png') }}" alt="icon">
                            Terraria
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        <div class="w-full">
            <!-- top appbar -->
            <div class="p-7 flex justify-start items-center h-16 bg-white">
                <div class="grid grid-cols-3 gap-6 text-center">
                    <a href="{{ route('dashboard.' . $game . '.index') }}" class="flex justify-center text-gray-700 font-medium {{ request()->routeIs('dashboard.' . $game . '.index') ? "bg-purple-100" : "bg-gray-100" }} p-2 rounded-md cursor-pointer hover:bg-purple-100">
                        <svg class="w-5 mr-2 text-purple-600" data-slot="icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z"></path>
                            <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z"></path>
                        </svg>
                        Visão Geral
                    </a>
                    <a href="{{ route('dashboard.' . $game . '.arquivos') }}" class="flex justify-center text-gray-700 font-medium {{ request()->routeIs('dashboard.' . $game . '.arquivos') ? "bg-purple-100" : "bg-gray-100" }} p-2 rounded-md cursor-pointer hover:bg-purple-100">
                        <svg class="w-5 mr-2 text-purple-600" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                            <path d="M384 480l48 0c11.4 0 21.9-6 27.6-15.9l112-192c5.8-9.9 5.8-22.1 .1-32.1S555.5 224 544 224l-400 0c-11.4 0-21.9 6-27.6 15.9L48 357.1 48 96c0-8.8 7.2-16 16-16l117.5 0c4.2 0 8.3 1.7 11.3 4.7l26.5 26.5c21 21 49.5 32.8 79.2 32.8L416 144c8.8 0 16 7.2 16 16l0 32 48 0 0-32c0-35.3-28.7-64-64-64L298.5 96c-17 0-33.3-6.7-45.3-18.7L226.7 50.7c-12-12-28.3-18.7-45.3-18.7L64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l23.7 0L384 480z"/>
                        </svg>
                        Arquivos
                    </a>
                    <a href="{{ route('dashboard.' . $game . '.config') }}" class="flex justify-center text-gray-700 font-medium {{ request()->routeIs('dashboard.' . $game . '.config') ? "bg-purple-100" : "bg-gray-100" }} p-2 rounded-md cursor-pointer hover:bg-purple-100">
                        <svg class="w-5 mr-2 text-purple-600" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                            <path d="M0 416c0 17.7 14.3 32 32 32l54.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 448c17.7 0 32-14.3 32-32s-14.3-32-32-32l-246.7 0c-12.3-28.3-40.5-48-73.3-48s-61 19.7-73.3 48L32 384c-17.7 0-32 14.3-32 32zm128 0a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM320 256a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32-80c-32.8 0-61 19.7-73.3 48L32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l246.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48l54.7 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-54.7 0c-12.3-28.3-40.5-48-73.3-48zM192 128a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm73.3-64C253 35.7 224.8 16 192 16s-61 19.7-73.3 48L32 64C14.3 64 0 78.3 0 96s14.3 32 32 32l86.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 128c17.7 0 32-14.3 32-32s-14.3-32-32-32L265.3 64z"/>
                        </svg>
                        Configurações
                    </a>
                </div>
            </div>
            <!-- top appbar -->
            <!-- Conteúdo principal -->
            <div class="flex-1 p-8">
                <h1 class="text-2xl font-bold mb-6 text-purple-700">Bem-vindo à sua Dashboard</h1>
                @yield('content')
    
            </div>
        </div>
    </div>
    <!-- limite de conteudo -->
    @vite('resources/js/app.js')
</body>
<footer class="mt-8 text-center text-sm text-gray-500">
    Server-Hub © 2025 • <a href="{{ route('sobre') }}" class="underline hover:text-gray-700">Sobre</a>
</footer>
</html>