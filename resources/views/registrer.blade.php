<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>

    <form>
        @csrf
        <div>
            <label for="name">Name</label>
            <input type="text" id="name" name="name">
        </div>

        <br>

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email">
        </div>

        <br>

        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password">
        </div>

        <br>

        <div>
            <label for="is_admin">Is Admin</label>
            <input type="checkbox" id="is_admin" name="is_admin">
        </div>

        <br>

        <button type="submit">Register</button>

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

</body>
</html>

<script>
    const form = document.querySelector('form');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const data = {
            name: form.name.value,
            email: form.email.value,
            password: form.password.value,
            is_admin: form.is_admin.checked
        };
        
        const csrfToken = document.querySelector('input[name="_token"]').value;

        const response = await fetch('{{ route('register.user') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: data
        });

        //const result = await response.json();

        console.log(response);
    });
</script>