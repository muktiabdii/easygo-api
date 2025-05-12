<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(UserController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login')->middleware('throttle:login');
    Route::post('/logout', 'logout');
    Route::post('/password/forgot', 'sendResetLinkEmail');
    Route::post('/password/validate-otp', 'validateOtp');
    Route::post('/password/reset', 'resetPassword');

    Route::put('/update', 'update')->middleware('auth:sanctum');
    Route::post('/update-profile-image', 'updateProfileImage')->middleware('auth:sanctum');
    
    // Token validation route
    Route::get('/validate-token', 'validateToken')->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    // Protected routes that require authentication
    Route::prefix('places')->group(function () {
        Route::post('/', [PlaceController::class, 'store']); 
        Route::get('{id}/has-reviewed', [ReviewController::class, 'hasReviewed']);
    });
    
    // Reviews routes
    Route::prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
    });
});

Route::prefix('places')->group(function () {
    Route::get('/', [PlaceController::class, 'index']); 
    Route::get('/{id}', [PlaceController::class, 'show']); 
    Route::get('/{id}/reviews', [ReviewController::class, 'getPlaceReviews']);
});