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
                <li>
                    <a href="#" class="p-2 font-bold text-base text-gray-900 hover:text-purple-800 hover:bg-gray-200 rounded-lg transition duration-400 ease-in-out">Painel Administrativo</a>
                </li>
                <li>
                    <a href="{{ route('servidores') }}" class="p-2 font-bold text-base text-gray-900 hover:text-purple-800 hover:bg-gray-200 rounded-lg transition duration-400 ease-in-out">Servidores</a>
                </li>
                <li>
                    <a href="{{ route('sobre') }}" class="p-2 font-bold text-base text-gray-900 hover:text-purple-800 hover:bg-gray-200 rounded-lg transition duration-400 ease-in-out">Sobre</a>
                </li>
            </ul>

        </div>
    </div>
    <!-- header -->
    <!-- banner -->
    <div style="background: url('{{ asset('imgs/banner/Banner.jpg') }}'); height: 597px;">
        <div class="container mx-auto"></div>
    </div>
<!-- banner -->
    @yield('content')
</body>
<footer class="mt-8 text-center text-sm text-gray-500">
    Server-Hub © 2025 • <a href="{{ route('sobre') }}" class="underline hover:text-gray-700">Sobre</a>
</footer>
</html>