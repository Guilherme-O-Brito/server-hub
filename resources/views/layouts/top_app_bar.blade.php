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
                @if (Auth::user()->is_admin)    
                <li>
                    <a href="" class="p-2 font-bold text-base text-gray-900 hover:text-purple-800 hover:bg-gray-200 rounded-lg transition duration-400 ease-in-out">Painel Administrativo</a>
                </li>
                @endif
                @endauth
                <li>
                    <a href="" class="p-2 font-bold text-base text-gray-900 hover:text-purple-800 hover:bg-gray-200 rounded-lg transition duration-400 ease-in-out">Servidores</a>
                </li>
                <li>
                    <a href="" class="p-2 font-bold text-base text-gray-900 hover:text-purple-800 hover:bg-gray-200 rounded-lg transition duration-400 ease-in-out">Sobre</a>
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
    @yield('content')
</body>
<footer class="mt-8 text-center text-sm text-gray-500">
    Server-Hub © 2026
</footer>
</html>