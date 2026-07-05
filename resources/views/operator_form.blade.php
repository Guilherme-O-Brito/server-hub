<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Minecraft Operator</title>
</head>
<body style="margin:0; min-height:100vh; display:flex; justify-content:center; align-items:center;">


<form method="POST" action="{{ route('create.minecraftServer.operator', $minecraftServer) }}"
      style="display:flex; flex-direction:column; gap:12px; min-width:300px;">

    @csrf

    <h2>Add Minecraft Operator</h2>

    <div>
        <label for="nickname">Nickname</label><br>
        <input
            type="text"
            id="nickname"
            name="nickname"
            value="{{ old('nickname') }}"
            required
        >
    </div>

    <button type="submit">
        Add Nickname
    </button>

</form>


</body>
</html>
