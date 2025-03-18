<?php

use App\Http\Controllers\DashBoardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/dashboard', [DashBoardController::class, 'index']);

Route::get('/login', function (){
    return view('login');
})->name('login');

Route::get('/servidores', function (){
    return view('servidores.index');
})->name('servidores');

Route::get('/sobre', function (){
    return view('sobre.index');
})->name('sobre');