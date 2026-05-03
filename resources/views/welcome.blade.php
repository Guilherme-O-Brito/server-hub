<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Server Hub</title>
</head>
<body>

    <div style="display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center;">
        <div>
            <h1>Server Hub</h1>
            <p>Gerencie seus servidores de jogos de forma simples.</p>

            <br>

            @auth
            <div class="container mx-auto mt-10 text-center text-gray-700">
                <h1 class="text-purple-600 font-bold text-5xl">Bem Vindo ao Server-Hub {{ Auth::user()->name }}! </h1>
                <p class="text-xl">Acesse a pagina <span class="text-purple-600 font-semibold">Servidores</span> para começar a jogar.</p> 
            </div>
            <a href="{{ route('logout') }}">
                    <button>Sair</button>
                </a>
            @endauth

            @guest
                <a href="{{ route('login') }}">
                    <button>Entrar</button>
                </a>

                <br><br>

                <a href="/register">
                    <button>Criar Conta</button>
                </a>
            @endguest
            
        </div>
    </div>

</body>
</html>