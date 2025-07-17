<?php

use App\Events\ConsoleOutput;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\AssettoCorsaDashBoardController;
use App\Http\Controllers\DashBoardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MinecraftDashBoardController;
use App\Http\Controllers\MinecraftServerController;
use App\Http\Controllers\TerrariaDashBoardController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response('', 200);
});

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/sobre', function (){
    return view('sobre.index');
})->name('sobre');

Route::middleware('guest')->group(function (){
    Route::get('/login', [LoginController::class, 'showLoginPage'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// middleware garantindo autenticação para algumas rotas
Route::middleware('auth')->group(function () {

    Route::get('/admin', [AdminPanelController::class, 'index'])->middleware(EnsureUserIsAdmin::class)->name('admin');
    Route::post('/create_user', [AdminPanelController::class, 'createUser'])->middleware(EnsureUserIsAdmin::class)->name('createUser');
    Route::post('/update/user/{id}', [AdminPanelController::class, 'updateUser'])->middleware(EnsureUserIsAdmin::class)->name('updateUser');
    Route::post('/delete/user/{id}', [AdminPanelController::class, 'deleteUser'])->middleware(EnsureUserIsAdmin::class)->name('deleteUser');

    // rota da pagina servidores
    Route::get('/servidores', function (){
        return view('servidores.index');
    })->name('servidores');

    // rotas da dashboard
    Route::prefix('/dashboard')->as('dashboard.')->group(function() {

        Route::prefix('/minecraft')->as('minecraft.')->group(function() {
            Route::get('/', [MinecraftDashBoardController::class, 'index'])->name('index');
            Route::get('/arquivos', [MinecraftDashBoardController::class, 'arquivos'])->name('arquivos');
            Route::get('/config', [MinecraftDashBoardController::class, 'config'])->name('config');
            Route::post('/start', [MinecraftServerController::class, 'start'])->name('startServer');
            Route::post('/stop', [MinecraftServerController::class, 'stop'])->name('stopServer');
            Route::post('/send-command', [MinecraftServerController::class, 'sendCommand'])->name('sendCommand');
        });
    
        Route::prefix('/assetto-corsa')->as('assettoCorsa.')->group(function() {
            Route::get('/', [AssettoCorsaDashBoardController::class, 'index'])->name('index');
            Route::get('/arquivos', [AssettoCorsaDashBoardController::class, 'arquivos'])->name('arquivos');
            Route::get('/config', [AssettoCorsaDashBoardController::class, 'config'])->name('config');
        });
    
        Route::prefix('/terraria')->as('terraria.')->group(function() {
            Route::get('/', [TerrariaDashBoardController::class, 'index'])->name('index');
            Route::get('/arquivos', [TerrariaDashBoardController::class, 'arquivos'])->name('arquivos');
            Route::get('/config', [TerrariaDashBoardController::class, 'config'])->name('config');
        });
    
    });
});