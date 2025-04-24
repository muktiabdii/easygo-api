<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(UserController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login')->middleware('throttle:login');
    Route::post('/password/forgot', 'sendResetLinkEmail');
    Route::post('/password/validate-otp', 'validateOtp');
    Route::post('/password/reset', 'resetPassword');
});
