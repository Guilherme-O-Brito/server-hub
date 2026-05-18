# Server-Hub
A project dedicated to creating a centralized way to manage self-hosted game servers such as Minecraft, Assetto Corsa, Terraria, etc.

## Table of Contents
* [Endpoints](#Endpoints)
* [Error Codes](#error-codes)
* [Rate Limiting](#rate-limiting)

---

## Getting Started

## Endpoints

### Get Console History (WIP)
Retrieves the last 200 messages from the running game console

* **URL:** `/api/console/history`
* **Method:** `GET`
* **Auth:** Required (Bearer Token)
* **Success Response:**
  * **Code:** `200 OK`
  * **Content:** 
    ```json
    [
        "Server started successfully",
        "Player joined the game",
        "Command executed"
    ]
    ```

---

### Get Server Resources (WIP)
Retrieves the total server memory, CPU and memory usage

* **URL:** `/api/server-status`
* **Method:** `GET`
* **Auth:** Required (Bearer Token)
* **Success Response:**
    * **Code:** `200 OK`
  * **Content:** 
    ```json
    {
        "cpu_usage": 75,
        "memory_usage": "512MB",
        "total_memory": "2GB"
    }
    ```

---

### Start Minecraft Server (WIP)
Starts the Minecraft server

* **URL:** `/api/minecraft/start`
* **Method:** `POST`
* **Auth:** Required
* **Success Response:**
    * **Code:** `202 Accepted`
    * **Content:**
     ```json
    {
        "message": "Server starting"
    }
    ```

---

### Stop Minecraft Server (WIP)
Stops the Minecraft server

* **URL:** `/api/minecraft/stop`
* **Method:** `POST`
* **Auth:** Required
* **Success Response:**
    * **Code:** `202 Accepted`
    * **Content:**
     ```json
    {
        "message": "Server stopping"
    }
    ```

---

### Minecraft Server Command (WIP)
Send commands to a running Minecraft server console

* **URL:** `/api/minecraft/command`
* **Method:** `POST`
* **Auth:** Required
* **Data Params:**
    ```json
    {
        "command": "say Hello world"
    }
    ```
* **Success Response:**
    * **Code:** `202 Accepted`
    * **Content:**
     ```json
    {
        "message": "Command sent successfully"
    }
    ```

---

### Sobre Page (WIP)
Returns the sobre page view

* **URL:** `/sobre`
* **Method:** `GET`
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### Admin Page (WIP)
Returns the admin page view

* **URL:** `/admin`
* **Method:** `GET`
* **Auth:** Required (Admin only)
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### Servers Page (WIP)
Returns the server page view

* **URL:** `/servers`
* **Method:** `GET`
* **Auth:** Required (Authenticated user)
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### Dashboard Page (WIP)
Returns the user dashboard page view

* **URL:** `/dashboard`
* **Method:** `GET`
* **Auth:** Required (Authenticated user)
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### Minecraft Dashboard Page (WIP)
Returns the user Minecraft dashboard page view

* **URL:** `/dashboard/minecraft`
* **Method:** `GET`
* **Auth:** Required (Authenticated user)
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### Home Page (WIP)
Returns the public home page view.

* **URL:** `/`
* **Method:** `GET`
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### Login Page (WIP)
Returns the login page view for guests.

* **URL:** `/login`
* **Method:** `GET`
* **Auth:** Guest only
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### Login User
Authenticates a user and starts a session.

* **URL:** `/login`
* **Method:** `POST`
* **Auth:** Guest only
* **Rate Limit:** 5 attempts per minute
* **Request Body:**
    * `email` (`string`, required) - User email address used to authenticate.
    * `password` (`string`, required) - User password used to authenticate.
* **Example Request:**
    ```json
    {
        "email": "john@example.com",
        "password": "secure_password"
    }
    ```
* **Success Response:**
    * **Code:** `302 Found`
    * **Content:** Redirect to the intended page after a successful login.
* **Possible Error Responses:**
    * **Code:** `302 Found`
    * **Content:** Redirect back with validation or authentication errors in the session.

---

### Logout User
Destroys the current authenticated session.

* **URL:** `/logout`
* **Method:** `POST`
* **Auth:** Required
* **Success Response:**
    * **Code:** `302 Found`
    * **Content:** Redirect to `/`.

---

### Create User
Creates a new user account.

* **URL:** `/user`
* **Method:** `POST`
* **Auth:** Required (Admin only)
* **Request Body:**
    * `name` (`string`, required) - Full name of the new user.
    * `email` (`string`, required) - Unique email address for the new user.
    * `password` (`string`, required) - Password that must meet the configured strength rules.
    * `is_admin` (`boolean`, required) - Defines whether the new user has admin privileges.
* **Example Request:**
    ```json
    {
        "name": "John Doe",
        "email": "john@example.com",
        "password": "secure_password",
        "is_admin": true
    }
    ```
* **Success Response:**
    * **Code:** `201 Created`
    * **Content:**
    ```json
    {
        "message": "User created successfully"
    }
    ```

---

### Update User
Updates an existing user account.

* **URL:** `/user/{user}`
* **Method:** `PUT`
* **Auth:** Required (Admin only)
* **Path Parameters:**
    * `user` (`integer`, required) - ID of the user to update.
* **Request Body:**
    * `name` (`string`, required) - Updated full name for the user.
    * `email` (`string`, required) - Updated email address for the user.
    * `password` (`string`, optional) - New password for the user. Leave empty to keep the current password.
    * `is_admin` (`boolean`, required) - Defines whether the user should remain or become an admin.
* **Example Request:**
    ```json
    {
        "name": "John Doe",
        "email": "john@example.com",
        "password": "new_password",
        "is_admin": false
    }
    ```
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:**
    ```json
    {
        "message": "User successfully modified"
    }
    ```

---

### Delete User
Deletes an existing user account.

* **URL:** `/user/{user}`
* **Method:** `DELETE`
* **Auth:** Required (Admin only)
* **Path Parameters:**
    * `user` (`integer`, required) - ID of the user to delete.
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:**
    ```json
    {
        "message": "User successfully deleted"
    }
    ```
* **Possible Error Responses:**
    * **Code:** `403 Forbidden`
    * **Content:**
    ```json
    {
        "message": "Are you dumb?" // returns this message if you try to delete yourself
    }
    ```

---

### Create Minecraft Server
Creates a new Minecraft server configuration for the authenticated user. The user is set as it's owner.

* **URL:** `/servers/minecraft`
* **Method:** `POST`
* **Auth:** Required
* **Request Body:**
    * `server_name` (`string`, required) - Name of the Minecraft server.
    * `level_name` (`string`, required) - Unique world level name used by the server.
    * `motd` (`string`, optional) - Message shown in the server list. Defaults to "{User name}'s minecraft server" if omitted.
    * `difficulty` (`integer`, required) - Server difficulty, from `0` to `3`.
    * `force_gamemode` (`boolean`, optional) - Forces players into the configured game mode when they join. Defaults to true if omitted.
    * `allow_flight` (`boolean`, optional) - Allows flight on the server. Defaults to true if omitted.
* **Example Request:**
    ```json
    {
        "server_name": "My Survival Server",
        "level_name": "world_01",
        "motd": "Welcome to my server",
        "difficulty": 2,
        "force_gamemode": true,
        "allow_flight": false
    }
    ```
* **Success Response:**
    * **Code:** `201 Created`
    * **Content:**
    ```json
    {
        "message": "Minecraft server created successfully"
    }
    ```

---

### Update Minecraft Server
Updates an existing Minecraft server configuration.

* **URL:** `/servers/minecraft/{minecraftServer}`
* **Method:** `PUT`
* **Auth:** Required
* **Policy:** You need to own this server
* **Path Parameters:**
    * `minecraftServer` (`integer`, required) - ID of the Minecraft server to update.
* **Request Body:**
    * `server_name` (`string`, required) - Updated name of the Minecraft server.
    * `motd` (`string`, optional) - Updated message shown in the server list. Defaults to "{User name}'s minecraft server" if omitted.
    * `difficulty` (`integer`, required) - Updated server difficulty, from `0` to `3`.
    * `force_gamemode` (`boolean`, optional) - Forces players into the configured game mode when they join. Defaults to true if omitted.
    * `allow_flight` (`boolean`, optional) - Allows flight on the server. Defaults to true if omitted.
* **Example Request:**
    ```json
    {
        "server_name": "My Survival Server",
        "motd": "Welcome back",
        "difficulty": 3,
        "force_gamemode": true,
        "allow_flight": true
    }
    ```
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:**
    ```json
    {
        "message": "Minecraft server successfully modified"
    }
    ```

---

### Delete Minecraft Server
Deletes an existing Minecraft server configuration.

* **URL:** `/servers/minecraft/{minecraftServer}`
* **Method:** `DELETE`
* **Auth:** Required
* **Policy:** You need to own this server
* **Path Parameters:**
    * `minecraftServer` (`integer`, required) - ID of the Minecraft server to delete.
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:**
    ```json
    {
        "message": "Server successfully deleted"
    }
    ```

---

### Add Minecraft Server Admin
Adds a user as an admin of a Minecraft server.

* **URL:** `/servers/minecraft/{minecraftServer}/admins/{user}`
* **Method:** `POST`
* **Auth:** Required
* **Policy:** You need to own this server
* **Path Parameters:**
    * `minecraftServer` (`integer`, required) - ID of the Minecraft server that will receive the admin.
    * `user` (`integer`, required) - ID of the user to add as an admin.
* **Success Response:**
    * **Code:** `201 Created`
    * **Content:**
    ```json
    {
        "message": "Admin added successfully."
    }
    ```
* **Possible Error Responses:**
    * **Code:** `422 Unprocessable Content`
    * **Content:**
    ```json
    {
        "message": "Owner is already the owner."
    }
    ```

---

### Remove Minecraft Server Admin
Removes a user from the admins of a Minecraft server.

* **URL:** `/servers/minecraft/{minecraftServer}/admins/{user}`
* **Method:** `DELETE`
* **Auth:** Required
* **Policy:** You need to own this server
* **Path Parameters:**
    * `minecraftServer` (`integer`, required) - ID of the Minecraft server to update.
    * `user` (`integer`, required) - ID of the user to remove from the admin list.
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:**
    ```json
    {
        "message": "Admin removed successfully."
    }
    ```
* **Possible Error Responses:**
    * **Code:** `404 Not Found`
    * **Content:**
    ```json
    {
        "message": "User is not an admin."
    }
    ```

---

## Error Codes

Common HTTP status codes returned by the APP:

| Code | Name | Description |
| :--- | :--- | :--- |
| 200 | OK | Request successful |
| 400 | Bad Request | Missing or invalid parameters |
| 401 | Unauthorized | Invalid or missing authentication |
| 404 | Not Found | Resource not found |
| 500 | Server Error | Internal server error |

---

## Rate Limiting

You can only do 5 login attempts per minute. Progressive bans will be applied starting with a 1-hour ban.