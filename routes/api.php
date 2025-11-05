<?php

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShowtimeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;



Route::get('/movies', function () {
    return Movie::with('genres')->orderBy('release_date', 'desc')->limit(60)->get();
});

Route::get('/movies/{id}', [MovieController::class, 'show']);
Route::get('/movies/{id}/showtimes', [ShowtimeController::class, 'getShowtimesByMovie']);


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user-profile', [UserController::class, 'profile']);
    Route::patch('/user-profile/password', [UserController::class, 'changePassword']);
    Route::patch('/user-profile', [UserController::class, 'updateProfile']);
});
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

