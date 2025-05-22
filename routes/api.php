<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->prefix('/auth')->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::apiResource('/posts', PostController::class);

    Route::get('/platforms', [PlatformController::class, 'index']);
    Route::post('/platforms/toggle', [PlatformController::class, 'toggle']);
});