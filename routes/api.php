<?php

use App\Http\Controllers\PlaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/places', [PlaceController::class, 'index']);
Route::post('/places', [PlaceController::class, 'store']);