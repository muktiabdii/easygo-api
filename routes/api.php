<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlaceController;

Route::prefix('auth')->controller(UserController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login')->middleware('throttle:login');
    Route::post('/password/forgot', 'sendResetLinkEmail');
    Route::post('/password/validate-otp', 'validateOtp');
    Route::post('/password/reset', 'resetPassword');
});

Route::prefix('places')->group(function () {
    Route::get('/', [PlaceController::class, 'index']); 
    Route::get('/{id}', [PlaceController::class, 'show']); 
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('places')->group(function () {
        Route::post('/', [PlaceController::class, 'store']);
    });

    Route::prefix('chat')->group(function () {
        Route::get('/', [ChatController::class, 'index']);
        Route::post('/', [ChatController::class, 'createRoom']);
        Route::get('/{chatRoomId}/messages', [ChatController::class, 'messages']);
        Route::post('/{chatRoomId}/messages', [ChatController::class, 'sendMessage']);
    });
});