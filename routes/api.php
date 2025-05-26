<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->prefix('/auth')->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
})->middleware('throttle:api');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/user', 'show');
        Route::put('/user', 'update');
        Route::delete('/user', 'destroy');
    });

    Route::apiResource('/posts', PostController::class);
    Route::get('/analytics', [PostController::class, 'analytics']);
    Route::get('/analytics/export', [PostController::class, 'exportAnalytics']);

    Route::get('/platforms', [PlatformController::class, 'index']);
    Route::post('/platforms/toggle', [PlatformController::class, 'toggle']);
});
