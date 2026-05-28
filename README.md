# Server-Hub
A project dedicated to creating a centralized way to manage self-hosted game servers such as Minecraft, Assetto Corsa, Terraria, etc.

## Table of Contents
* [Endpoints](#Endpoints)
* [Error Codes](#error-codes)
* [Rate Limiting](#rate-limiting)
* [Kubernetes Architecture](#kubernetes-architecture)

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

---

## Kubernetes Architecture

This project includes a recommended Kubernetes-based deployment architecture under the `k8s/` folder. It is the preferred way to run and validate Server-Hub before releasing updates, but it is not mandatory. If you prefer, you can ignore the Kubernetes manifests and use the Docker image published in the releases, then configure everything yourself.

The architecture is designed to be practical for both production and home-lab environments. For smaller self-hosted setups, K3s is usually lighter and easier to maintain than full Kubernetes, but both K3s and Kubernetes are valid choices.

### Visual Reference
![Kubernetes Architecture](k8s/k3s%20diagram.png)
If you keep the architecture diagram next to this documentation, use it as the visual reference for the deployment flow. It is a good example of the kind of diagram that helps explain how the stack is connected.

### How the `k8s/` folder is organized

The manifests are split by responsibility so each part of the platform is easy to understand and maintain:

* `k8s/platform/` contains the core application infrastructure.
* `k8s/platform/namespace.yaml` isolates the main platform resources from the rest of the cluster.
* `k8s/platform/laravel/` is where the web application workloads and supporting configuration live.
* `k8s/platform/mariadb/` provides the database layer used by the web application.
* `k8s/platform/redis/` provides cache and queue support for the application.
* `k8s/infrastructure/cloudflared.yaml` represents the tunnel layer used to expose the web app securely.
* `k8s/games/` separates game-related workloads from the platform layer.
* `k8s/games/namespace.yaml` keeps game services isolated from the web stack.
* `k8s/games/minecraft/` is the example game workload for Minecraft.

This separation is intentional. The web platform, persistent services, and game workloads are isolated so that each part can be scaled, secured, and updated independently.

### What this architecture is doing

At a high level, the platform is built around three ideas:

1. The Laravel web app runs inside the cluster and talks to internal services such as MariaDB and Redis.
2. Cloudflared or a similar tunnel layer exposes the web application to the internet without opening the web app directly to the public network.
3. Game workloads are kept separate from the web platform so the application can manage them independently.

The Minecraft workload inside `k8s/games/minecraft/` should be treated as an example of how the app can create or manage a server dynamically. It is there to show the pattern, not because you are required to use this exact workload layout.

### Important deployment requirements

This architecture assumes that the physical server running the project has Kubernetes available and that the web application has network access to the Kubernetes API it needs to manage workloads. If the app cannot reach the cluster API, the dynamic server management flow will not work.

Running the application outside Kubernetes may still be possible, but that path is not tested here and is entirely up to the user to figure out and maintain. The recommended path is to use the cluster-based architecture shown in this repository.

### Environment variables and placeholders

Several manifests use placeholder values written between `< >` so you can fill them in for your own environment. These placeholders are important because they prevent hardcoding sensitive values and make the same architecture reusable across different servers.

### Benefits of this architecture

This layout gives you several advantages:

* Security: internal services stay inside the cluster, while only the intended entry points are exposed.
* Separation of concerns: web, database, cache, infrastructure, and game workloads are isolated from each other.
* Easier maintenance: each part can be tested and updated independently.
* Better portability: the same manifests can be adapted for K3s, Kubernetes, and different hosting environments.
* Better observability and debugging: each workload has a clear role and can be inspected separately.
* Better scaling options: the web app, cache, database, and game layer can evolve without forcing a single monolithic deployment.

Performance also benefits from this structure. Redis can absorb cache and queue work, MariaDB stays dedicated to persistence, and the web application does not have to manage all concerns in the same container. This generally reduces contention and makes resource usage easier to tune.

### Security notes

This project is built with security in mind, but no system is perfect or impenetrable. For production use on the public internet, it is strongly recommended to place the web application behind a tunnel such as Cloudflare Tunnel or a similar service.

If you use a tunnel provider, configure it so that it never accepts plain HTTP from outside your local network and always prefers HTTPS for external traffic. Also enable every security control the provider offers, such as access policies, authentication controls, and request filtering.

It is also strongly recommended to configure a Web Application Firewall (WAF) on your side. This documentation does not cover WAF setup because it is outside the scope of this project.

Even with a tunnel, HTTPS, and a WAF, the Minecraft server remains the weakest security point because game traffic cannot be hidden behind an HTTP tunnel in the same way the web app can. If you want players to connect without installing extra software such as a VPN or a game tunnel, you will need to expose the Minecraft port on your host firewall and router.

In this project, that commonly means opening port `30000` for Minecraft. If you are behind CGNAT or your provider blocks inbound ports, you can use IPv6 instead, but then your Kubernetes cluster must also be configured for IPv6 support, whether you are using K3s or Kubernetes.

These port-opening and IPv6 approaches are not the most secure options available. They are the weakest link in the security chain and should only be used at your own risk.

For the safest possible setup, a VPN or a peer-to-peer tunnel for game traffic is usually better, even if it is less convenient for players. That avoids exposing the Minecraft server directly to the public network.

Do not modify this project to remove the whitelist requirement for a publicly exposed Minecraft server unless you fully understand the risk. Because this project is open source, you can do it, but that choice is entirely on you, especially if the server is reachable from the public internet.

### Recommended, but not required

This architecture is recommended because it will be kept tested before project updates are released. That makes it the safest and most reliable path for users who want a known-good deployment.

At the same time, it is still optional. You can use the released Docker image and build your own environment if you prefer. The repository documents the Kubernetes path because it is the one we expect to validate regularly, not because every installation must use it.

### Practical summary

If you want the most balanced setup, use Kubernetes or K3s for the platform, keep the web app behind a tunnel, secure the cluster with a WAF, and expose Minecraft only if you accept the risk. If you want maximum convenience instead, you can run the Docker image outside Kubernetes, but that route is not covered or tested here.