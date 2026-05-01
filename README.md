# Server-Hub
A project dedicated to creating a centralized way to manage self-hosted game servers such as Minecraft, Assetto Corsa, Terraria, etc.

## Table of Contents
* [Authentication](#authentication)
* [API Endpoints](#API-Endpoints)
* [Web Routes](#Web-Routes)
* [Error Codes](#error-codes)
* [Rate Limiting](#rate-limiting)

---

## Getting Started

## Authentication
WIP

---

## API Endpoints

### 1. Get Console History
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

### 2. Get Server Resources
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

### 3. Create User
Creates a new user

* **URL:** `/api/users`
* **Method:** `POST`
* **Auth:** Required (Admin only)
* **Data Params:**
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

### 4. Update User
Update an existing user

* **URL:** `/api/users/{id}`
* **Method:** `PUT`
* **Auth:** Required (Admin only)
* **Data Params:**
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
        "message": "User updated successfully"
    }
    ```

---

### 5. Delete User
Delete an existing user

* **URL:** `/api/users/{id}`
* **Method:** `DELETE`
* **Auth:** Required (Admin only)
* **Success Response:**
    * **Code:** `204 No Content`

---

### 6. Start Minecraft Server (WIP)
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

### 7. Stop Minecraft Server (WIP)
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

### 8. Minecraft Server Command (WIP)
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

## Web Routes

### 1. Home Page
Returns the home page view

* **URL:** `/`
* **Method:** `GET`
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### 2. Sobre Page
Returns the sobre page view

* **URL:** `/sobre`
* **Method:** `GET`
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### 3. Login Page
Returns the login page view

* **URL:** `/login`
* **Method:** `GET`
* **Auth:** Non-authenticated only
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### 4. Login
Send the login form

* **URL:** `/login`
* **Method:** `POST`
* **Data Params:**
    ```json
    {
        "email": "john@example.com",
        "password": "secure_password"
    }
    ```
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `session`

---

### 5. Logout
Destroys your login session

* **URL:** `/logout`
* **Method:** `POST`
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `Home HTML Page`

---

### 6. Admin Page
Returns the admin page view

* **URL:** `/admin`
* **Method:** `GET`
* **Auth:** Required (Admin only)
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### 7. Servers Page
Returns the server page view

* **URL:** `/servers`
* **Method:** `GET`
* **Auth:** Required (Authenticated user)
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### 8. Dashboard Page
Returns the user dashboard page view

* **URL:** `/dashboard`
* **Method:** `GET`
* **Auth:** Required (Authenticated user)
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

### 9. Minecraft Dashboard Page
Returns the user Minecraft dashboard page view

* **URL:** `/dashboard/minecraft`
* **Method:** `GET`
* **Auth:** Required (Authenticated user)
* **Success Response:**
    * **Code:** `200 OK`
    * **Content:** `HTML Page`

---

## Error Codes

Common HTTP status codes returned by the API:

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