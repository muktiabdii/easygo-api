<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlaceController;

Route::prefix('auth')->controller(UserController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login')->middleware('throttle:login');
    Route::post('/logout', 'logout');
    Route::post('/password/forgot', 'sendResetLinkEmail');
    Route::post('/password/validate-otp', 'validateOtp');
    Route::post('/password/reset', 'resetPassword');
    
    // Token validation route
    Route::get('/validate-token', 'validateToken')->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    // Protected routes that require authentication
    Route::prefix('places')->group(function () {
        Route::post('/', [PlaceController::class, 'store']); 
    });
});

Route::prefix('places')->group(function () {
    Route::get('/', [PlaceController::class, 'index']); 
    Route::get('/{id}', [PlaceController::class, 'show']); 
});