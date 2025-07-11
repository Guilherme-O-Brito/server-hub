<?php

use App\Http\Controllers\ServerStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware( 'auth:sanctum')->get('/server-status', [ServerStatusController::class, 'status']);

Route::middleware('auth:sanctum')->get('/console/history', function () {
    try {
        $response = Redis::lrange('console:log', 0, 199);
        $response = array_reverse($response);
    } catch (\Throwable $th) {
        $response = $th;
    }

    return $response;
});