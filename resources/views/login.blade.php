<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>

    <div style="display: flex; justify-content: center; align-items: center; height: 100vh;">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div style="text-align: center;">
                <h2>Login</h2>

                <div>
                    <label for="email">Email:</label><br>
                    <input type="email" id="email" name="email" required>
                </div>

                <br>

                <div>
                    <label for="password">Senha:</label><br>
                    <input type="password" id="password" name="password" required>
                </div>

                <br>

                <button type="submit">Entrar</button>
            </div>
        </form>
        @if ($errors->any())
            <div class="bg-red-100 text-red-600 px-3 py-2 rounded mt-2">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

</body>
</html>