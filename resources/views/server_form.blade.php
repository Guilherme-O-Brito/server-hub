<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Minecraft Server</title>
</head>
<body style="margin:0; min-height:100vh; display:flex; justify-content:center; align-items:center;">


<form method="POST" action="{{ route('create.minecraftServer') }}"
      style="display:flex; flex-direction:column; gap:12px; min-width:300px;">

    @csrf

    <h2>Create Minecraft Server</h2>

    <div>
        <label for="server_name">Server Name</label><br>
        <input
            type="text"
            id="server_name"
            name="server_name"
            value="{{ old('server_name') }}"
            required
        >
    </div>

    <div>
        <label for="motd">MOTD</label><br>
        <input
            type="text"
            id="motd"
            name="motd"
            value="{{ old('motd') }}"
        >
    </div>

    <div>
        <label for="difficulty">Difficulty</label><br>
        <select id="difficulty" name="difficulty" required>
            <option value="0">Peaceful</option>
            <option value="1">Easy</option>
            <option value="2">Normal</option>
            <option value="3">Hard</option>
        </select>
    </div>

    <label>
        <input
            type="checkbox"
            name="force_gamemode"
            value="1"
            checked
        >
        Force Gamemode
    </label>

    <label>
        <input
            type="checkbox"
            name="allow_flight"
            value="1"
            checked
        >
        Allow Flight
    </label>

    <button type="submit">
        Create Server
    </button>

</form>


</body>
</html>
