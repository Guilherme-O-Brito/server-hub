<?php

use App\Http\Controllers\DashBoardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/dashboard', [DashBoardController::class, 'index']);
Route::get('/login', function (){
    return view('login');
});