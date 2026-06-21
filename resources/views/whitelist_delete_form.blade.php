<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Minecraft Whitelist</title>
</head>
<body style="margin:0; min-height:100vh; display:flex; justify-content:center; align-items:center;">

<form method="POST" action="{{ route('delete.minecraftServer.whitelist', [$minecraftServer, $minecraftWhitelist]) }}" style="display:flex; flex-direction:column; gap:12px; min-width:300px;">
    @csrf
    @method('DELETE')

    <h2>Delete Minecraft Whitelist</h2>

    <button type="submit">Delete</button>
</form>

</body>
</html>
