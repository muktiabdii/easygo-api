<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\ReviewController;
use Illuminate\Http\Request;

Route::prefix('auth')->controller(UserController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login')->middleware('throttle:login');
    Route::post('/admin-login', 'adminLogin')->middleware('throttle:login');
    Route::post('/logout', 'logout');
    Route::post('/password/forgot', 'sendResetLinkEmail');
    Route::post('/password/validate-otp', 'validateOtp');
    Route::post('/password/reset', 'resetPassword');
    Route::get('/validate-token', 'validateToken')->middleware('auth:sanctum');
    Route::put('/update', 'update')->middleware('auth:sanctum');
    Route::post('/update-profile-image', 'updateProfileImage')->middleware('auth:sanctum');
    Route::get('/validate-token', 'validateToken')->middleware('auth:sanctum');
    Route::get('/user', [UserController::class, 'getAuthenticatedUserId'])->middleware('auth:sanctum');
});


Route::prefix('places')->group(function () {
    Route::get('/', [PlaceController::class, 'index']); 
    Route::get('/{id}', [PlaceController::class, 'show']); 
    Route::get('/{id}/reviews', [ReviewController::class, 'getPlaceReviews']);
});


Route::middleware('auth:sanctum')->group(function () {
    // Protected routes that require authentication
    Route::prefix('places')->group(function () {
        Route::post('/', [PlaceController::class, 'store']);
        Route::get('{id}/has-reviewed', [ReviewController::class, 'hasReviewed']);
        Route::put('{id}/approve', [PlaceController::class, 'approve']);
        Route::put('{id}/reject', [PlaceController::class, 'reject']);
        Route::delete('{id}', [PlaceController::class, 'destroy']);
        Route::get('/admin', [PlaceController::class, 'pending']);
    });

    // Reviews routes
    Route::prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::get('/user', [ReviewController::class, 'getUserReviews']); // New route for user reviews
    });

    Route::prefix('chat')->group(function () {
        Route::get('/', [ChatController::class, 'index']);
        Route::post('/', [ChatController::class, 'createRoom']);
        Route::get('/{chatRoomId}/messages', [ChatController::class, 'messages']);
        Route::post('/{chatRoomId}/messages', [ChatController::class, 'sendMessage']);
        Route::post('/messages/search', [ChatController::class, 'searchMessages']);
        });

        Route::get('/users/search', [UserController::class, 'searchUsers']);
        Route::get('/user', [UserController::class, 'getAuthenticatedUserId']);
});

