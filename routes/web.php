<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\MinecraftServerController;
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
        Route::put('/{id}', [UserController::class, 'update'])->name('update.user');
        Route::delete('/{id}', [UserController::class, 'delete'])->name('delete.user');
        //Route::get('/', [UserController::class, 'index']);
    });

    Route::get('/register', function () {
        return view('registrer');
    });
});

Route::middleware('auth')->group(function () {
    Route::prefix('/server/minecraft')->group(function () {
        Route::post('/', [MinecraftServerController::class, 'create'])->name('register.minecraftServer');
        Route::put('/{id}', [MinecraftServerController::class, 'update'])->name('update.minecraftServer');
        Route::delete('/{id}', [MinecraftServerController::class, 'delete'])->name('delete.minecraftServer');
    });
});


    