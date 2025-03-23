<?php

use App\Http\Controllers\AssettoCorsaDashBoardController;
use App\Http\Controllers\DashBoardController;
use App\Http\Controllers\MinecraftDashBoardController;
use App\Http\Controllers\TerrariaDashBoardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::prefix('/dashboard')->as('dashboard.')->group(function() {

    Route::prefix('/minecraft')->as('minecraft.')->group(function() {
        Route::get('/', [MinecraftDashBoardController::class, 'index'])->name('index');
        Route::get('/arquivos', [MinecraftDashBoardController::class, 'arquivos'])->name('arquivos');
        Route::get('/config', [MinecraftDashBoardController::class, 'config'])->name('config');
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

Route::get('/login', function (){
    return view('login');
})->name('login');

Route::get('/servidores', function (){
    return view('servidores.index');
})->name('servidores');

Route::get('/sobre', function (){
    return view('sobre.index');
})->name('sobre');