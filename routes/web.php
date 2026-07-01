<?php

use App\Http\Controllers\ExecutionSlotController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MinecraftServerAdminController;
use App\Http\Controllers\MinecraftServerController;
use App\Http\Controllers\MinecraftVersionController;
use App\Http\Controllers\MinecraftWhitelistController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('/login')->middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'LoginView'])->name('login');
    Route::post('/', [LoginController::class, 'authenticate'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// authentication and admin only
Route::prefix('/user')->middleware(['auth', EnsureUserIsAdmin::class])->group(function () {
    // user CRUD
    Route::post('/', [UserController::class, 'create'])->name('register.user');
    Route::put('/{user}', [UserController::class, 'update'])->name('update.user');
    Route::delete('/{user}', [UserController::class, 'delete'])->name('delete.user');
});

Route::prefix('/execution-slot')->middleware('auth')->group(function () {
    Route::post('/', [ExecutionSlotController::class, 'create_one'])->middleware(EnsureUserIsAdmin::class)->name('create_one.execution_slot');
    Route::delete('/', [ExecutionSlotController::class, 'delete_last'])->middleware(EnsureUserIsAdmin::class)->name('delete_last.execution_slot');
    Route::get('/', [ExecutionSlotController::class, 'index'])->name('get.execution_slot');
});

Route::prefix('/servers')->group(function () {
    Route::prefix('/minecraft')->middleware('auth')->group(function () {
        // minecraft CRUD
        Route::post('/', [MinecraftServerController::class, 'create'])->name('create.minecraftServer');
        Route::put('/{minecraftServer}', [MinecraftServerController::class, 'update'])->name('update.minecraftServer');
        Route::delete('/{minecraftServer}', [MinecraftServerController::class, 'delete'])->name('delete.minecraftServer');
        // minecraft start and stop
        Route::post('/{minecraftServer}/start', [MinecraftServerController::class, 'start'])->name('start.minecraftServer');
        Route::post('/{minecraftServer}/stop', [MinecraftServerController::class, 'stop'])->name('stop.minecraftServer');
        // minecraft server admin create and delete
        Route::post('/{minecraftServer}/admins/{user}', [MinecraftServerAdminController::class, 'store'])->name('store.minecraftServer.admin');
        Route::delete('/{minecraftServer}/admins/{user}', [MinecraftServerAdminController::class, 'delete'])->name('delete.minecraftServer.admin');
        Route::get('/{minecraftServer}/admins', [MinecraftServerAdminController::class, 'index'])->name('get.minecraftServer.admin');
        // minecraft server whitelist CRUD
        Route::prefix('/{minecraftServer}/whitelist')->group(function () {
            Route::post('/', [MinecraftWhitelistController::class, 'create'])->name('store.minecraftServer.whitelist');
            Route::delete('/{minecraftWhitelist}', [MinecraftWhitelistController::class, 'delete'])->name('delete.minecraftServer.whitelist');
            Route::get('/', [MinecraftWhitelistController::class, 'index'])->name('get.minecraftServer.whitelist');    
        });
        // minecraft server versions CRUD
        Route::prefix('/version')->group(function () {
            Route::post('/', [MinecraftVersionController::class, 'create'])->middleware(EnsureUserIsAdmin::class)->name('create.minecraftVersion');
            Route::post('/{minecraftVersion}/toggle', [MinecraftVersionController::class, 'toggle'])->middleware(EnsureUserIsAdmin::class)->name('toggle.minecraftVersion');
            Route::delete('/{minecraftVersion}', [MinecraftVersionController::class, 'delete'])->middleware(EnsureUserIsAdmin::class)->name('delete.minecraftVersion');
            Route::get('/', [MinecraftVersionController::class, 'index'])->name('get.minecraftVersion');
        });
    });
});

// temporary test routes
Route::middleware('auth')->group(function () {
    Route::get('/servers/minecraft/create', function () {
        return view('server_form');
    });

    Route::get('/servers/minecraft/update/{minecraftServer}', function (\App\Models\MinecraftServer $minecraftServer) {
        return view('server_edit_form', compact('minecraftServer'));
    });

    Route::get('/servers/minecraft/delete/{minecraftServer}', function (\App\Models\MinecraftServer $minecraftServer) {
        return view('server_delete_form', compact('minecraftServer'));
    });

    Route::get('/servers/minecraft/start/{minecraftServer}', function (\App\Models\MinecraftServer $minecraftServer) {
        return view('server_start_form', compact('minecraftServer'));
    });

    Route::get('/servers/minecraft/stop/{minecraftServer}', function (\App\Models\MinecraftServer $minecraftServer) {
        return view('server_stop_form', compact('minecraftServer'));
    });

    Route::get('/servers/minecraft/{minecraftServer}/whitelist/create', function (\App\Models\MinecraftServer $minecraftServer) {
        return view('whitelist_form', compact('minecraftServer'));
    });

    Route::get('/servers/minecraft/{minecraftServer}/whitelist/delete/{minecraftWhitelist}', function (\App\Models\MinecraftServer $minecraftServer, \App\Models\MinecraftWhitelist $minecraftWhitelist) {
        if ($minecraftWhitelist->minecraft_server_id !== $minecraftServer->id) {
            abort(404);
        }

        return view('whitelist_delete_form', compact('minecraftServer', 'minecraftWhitelist'));
    });

    Route::get('/servers/minecraft/version/create', function () {
        return view('minecraft_version_form');
    });

    Route::get('/servers/minecraft/version/toggle/{minecraftVersion}', function (\App\Models\MinecraftVersion $minecraftVersion) {
        return view('minecraft_version_toggle_form', compact('minecraftVersion'));
    });

    Route::get('/servers/minecraft/version/delete/{minecraftVersion}', function (\App\Models\MinecraftVersion $minecraftVersion) {
        return view('minecraft_version_delete_form', compact('minecraftVersion'));
    });

    Route::get('/execution-slot/create', function () {
        return view('execution_slot_form');
    });

    Route::get('/execution-slot/delete', function () {
        return view('execution_slot_delete_form');
    });

});
