<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (){
    Route::get('/login', [LoginController::class, 'LoginView'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// authenticated admins routes
Route::middleware(['auth', EnsureUserIsAdmin::class])->group(function () {
    // user CRUD
    Route::prefix('/user')->group(function () {
        Route::post('/', [UserController::class, 'create'])->name('register.user');
        Route::put('/', [UserController::class, 'update']);
        Route::delete('/', [UserController::class, 'delete']);
        Route::get('/', [UserController::class, 'index']);
    });

    Route::get('/register', function () {
        return view('registrer');
    });
});
    