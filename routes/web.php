<?php

use App\Http\Controllers\ExecutionSlotController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MinecraftOperatorController;
use App\Http\Controllers\MinecraftServerAdminController;
use App\Http\Controllers\MinecraftServerController;
use App\Http\Controllers\MinecraftVersionController;
use App\Http\Controllers\MinecraftWhitelistController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('/login')->middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'LoginView'])->name('login');
    Route::post('/', [LoginController::class, 'authenticate'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/user', [UserController::class, 'index'])->middleware('auth')->name('index.user');

// authentication and admin only
Route::prefix('/admin/user')->middleware(['auth', EnsureUserIsAdmin::class])->group(function () {
    Route::get('/', [UserController::class, 'adminIndex'])->name('index.user.admin');
    Route::post('/', [UserController::class, 'create'])->name('create.user');
    Route::put('/{user}', [UserController::class, 'update'])->name('update.user');
    Route::delete('/{user}', [UserController::class, 'delete'])->name('delete.user');
});

Route::prefix('/execution-slot')->middleware('auth')->group(function () {
    Route::post('/', [ExecutionSlotController::class, 'create_one'])->middleware(EnsureUserIsAdmin::class)->name('create_one.execution_slot');
    Route::delete('/', [ExecutionSlotController::class, 'delete_last'])->middleware(EnsureUserIsAdmin::class)->name('delete_last.execution_slot');
    Route::get('/', [ExecutionSlotController::class, 'index'])->name('index.execution_slot');
});

Route::prefix('/admin/servers/minecraft/version')->middleware(['auth', EnsureUserIsAdmin::class])->group(function () {
    Route::get('/', [MinecraftVersionController::class, 'adminIndex'])->name('index.minecraftVersion.admin');
    Route::post('/', [MinecraftVersionController::class, 'create'])->name('create.minecraftVersion');
    Route::post('/{minecraftVersion}/toggle', [MinecraftVersionController::class, 'toggle'])->whereNumber('minecraftVersion')->name('toggle.minecraftVersion');
    Route::delete('/{minecraftVersion}', [MinecraftVersionController::class, 'delete'])->whereNumber('minecraftVersion')->name('delete.minecraftVersion');
});

Route::prefix('/servers')->group(function () {
    Route::prefix('/minecraft')->middleware('auth')->group(function () {
        // minecraft CRUD
        Route::post('/', [MinecraftServerController::class, 'create'])->name('create.minecraftServer');
        Route::put('/{minecraftServer}', [MinecraftServerController::class, 'update'])->whereNumber('minecraftServer')->name('update.minecraftServer');
        Route::delete('/{minecraftServer}', [MinecraftServerController::class, 'delete'])->whereNumber('minecraftServer')->name('delete.minecraftServer');
        Route::get('/', [MinecraftServerController::class, 'index'])->name('index.minecraftServer');
        Route::get('/{minecraftServer}', [MinecraftServerController::class, 'get'])->whereNumber('minecraftServer')->name('get.minecraftServer');
        // minecraft start and stop
        Route::post('/{minecraftServer}/start', [MinecraftServerController::class, 'start'])->whereNumber('minecraftServer')->name('start.minecraftServer');
        Route::post('/{minecraftServer}/stop', [MinecraftServerController::class, 'stop'])->whereNumber('minecraftServer')->name('stop.minecraftServer');
        // minecraft server admin create and delete
        Route::post('/{minecraftServer}/admins/{user}', [MinecraftServerAdminController::class, 'store'])->whereNumber(['minecraftServer', 'user'])->name('store.minecraftServer.admin');
        Route::delete('/{minecraftServer}/admins/{user}', [MinecraftServerAdminController::class, 'delete'])->whereNumber(['minecraftServer', 'user'])->name('delete.minecraftServer.admin');
        Route::get('/{minecraftServer}/admins', [MinecraftServerAdminController::class, 'index'])->whereNumber('minecraftServer')->name('index.minecraftServer.admin');
        // minecraft server whitelist CRUD
        Route::prefix('/{minecraftServer}/whitelist')->group(function () {
            Route::post('/', [MinecraftWhitelistController::class, 'create'])->name('create.minecraftServer.whitelist');
            Route::delete('/{minecraftWhitelist}', [MinecraftWhitelistController::class, 'delete'])->whereNumber('minecraftWhitelist')->name('delete.minecraftServer.whitelist');
            Route::get('/', [MinecraftWhitelistController::class, 'index'])->name('index.minecraftServer.whitelist');    
        });
        // minecraft server operators crud
        Route::prefix('/{minecraftServer}/operators')->group(function () {
            Route::post('/', [MinecraftOperatorController::class, 'create'])->name('create.minecraftServer.operator');
            Route::delete('/{minecraftOperator}', [MinecraftOperatorController::class, 'delete'])->whereNumber('minecraftOperator')->name('delete.minecraftServer.operator');
            Route::get('/', [MinecraftOperatorController::class, 'index'])->name('index.minecraftServer.operator');    
        });
        // index minecraft versions
        Route::get('/version', [MinecraftVersionController::class, 'index'])->name('index.minecraftVersion');
        
    });
});

// view routes
Route::view('/admin', 'admin.index')
    ->middleware(['auth', EnsureUserIsAdmin::class])
    ->name('admin.view');

Route::view('/servidores', 'servidores.index')
    ->middleware('auth')
    ->name('servers.view');

Route::view('/servidores/minecraft/{minecraftServer}', 'servidores.index')
    ->whereNumber('minecraftServer')
    ->middleware('auth')
    ->name('servers.minecraft.view');

Route::get('/', function () {
    return Auth::check() ? redirect()->route('servers.view') : view('home');
})->name('home');
