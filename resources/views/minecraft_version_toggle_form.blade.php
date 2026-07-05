<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toggle Minecraft Version</title>
</head>
<body style="margin:0; min-height:100vh; display:flex; justify-content:center; align-items:center;">

<form method="POST" action="{{ route('toggle.minecraftVersion', $minecraftVersion) }}" style="display:flex; flex-direction:column; gap:12px; min-width:300px;">
    @csrf

    <h2>Toggle Minecraft Version</h2>

    <button type="submit">Toggle</button>
</form>

</body>
</html>
