<?php

use App\Http\Controllers\ExecutionSlotController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MinecraftServerAdminController;
use App\Http\Controllers\MinecraftServerController;
use App\Http\Controllers\MinecraftWhitelistController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (){
    Route::get('/login', [LoginController::class, 'LoginView'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// authenticated admin routes
Route::middleware(['auth', EnsureUserIsAdmin::class])->group(function () {
    // user CRUD
    Route::prefix('/user')->group(function () {
        Route::post('/', [UserController::class, 'create'])->name('register.user');
        Route::put('/{user}', [UserController::class, 'update'])->name('update.user');
        Route::delete('/{user}', [UserController::class, 'delete'])->name('delete.user');
        //Route::get('/', [UserController::class, 'index']);
    });
    // execution slot create and delete
    Route::prefix('/execution-slot')->group(function () {
        Route::post('/', [ExecutionSlotController::class, 'create_one'])->name('create_one.execution_slot');
        Route::delete('/', [ExecutionSlotController::class, 'delete_last'])->name('delete_last.execution_slot');
    });
});

Route::middleware('auth')->group(function () {
    // get execution slots
    Route::get('/execution-slot', [ExecutionSlotController::class, 'index'])->name('get.execution_slot');
    // servers crud
    Route::prefix('/servers/minecraft')->group(function () {
        Route::post('/', [MinecraftServerController::class, 'create'])->name('create.minecraftServer');
        Route::put('/{minecraftServer}', [MinecraftServerController::class, 'update'])->name('update.minecraftServer');
        Route::delete('/{minecraftServer}', [MinecraftServerController::class, 'delete'])->name('delete.minecraftServer');
        Route::post('/{minecraftServer}/admins/{user}', [MinecraftServerAdminController::class, 'store'])->name('store.minecraftServer.admin');
        Route::delete('/{minecraftServer}/admins/{user}', [MinecraftServerAdminController::class, 'delete'])->name('delete.minecraftServer.admin');
        Route::prefix('/{minecraftServer}/whitelist')->group(function () {
            Route::post('/', [MinecraftWhitelistController::class, 'create'])->name('store.minecraftServer.whitelist');
            Route::delete('/{minecraftWhitelist}', [MinecraftWhitelistController::class, 'delete'])->name('delete.minecraftServer.whitelist');
            Route::get('/', [MinecraftWhitelistController::class, 'index'])->name('get.minecraftServer.whitelist');    
        });
    });
    Route::get('/servers/minecraft/create', function () {
        return view('server_form');
    });
});