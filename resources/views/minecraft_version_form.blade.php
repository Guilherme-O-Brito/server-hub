<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Minecraft Version</title>
</head>
<body style="margin:0; min-height:100vh; display:flex; justify-content:center; align-items:center;">


<form method="POST" action="{{ route('create.minecraftVersion') }}"
      style="display:flex; flex-direction:column; gap:12px; min-width:300px;">

    @csrf

    <h2>Create Minecraft Version</h2>

    <div>
        <label for="version">Version</label><br>
        <input
            type="text"
            id="version"
            name="version"
            value="{{ old('version') }}"
            required
        >
    </div>

    <label>
        <input type="hidden" name="is_enabled" value="0">
        <input
            type="checkbox"
            name="is_enabled"
            value="1"
            checked
        >
        Enabled
    </label>

    <button type="submit">
        Create Minecraft Version
    </button>

</form>


</body>
</html>
