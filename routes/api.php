<?php

use App\Models\Movie;
use App\Models\Theater;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShowtimeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\TheaterController;
use App\Http\Controllers\Api\FoodController;
use App\Http\Controllers\Api\SeatTypeController;
use App\Http\Controllers\Api\TimeSlotModifierController;
use App\Http\Controllers\Api\DayModifierController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\SeatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========== PUBLIC ROUTES ==========

// Movies
Route::get('/movies', function () {
    return Movie::with('genres')->orderBy('release_date', 'desc')->limit(60)->get();
});
Route::get('/movies/{id}', [MovieController::class, 'show']);
Route::get('/movies/{id}/showtimes', [ShowtimeController::class, 'getShowtimesByMovie']);

// Theaters
Route::apiResource('theaters', TheaterController::class);
Route::get('/theaters/{id}/rooms', [TheaterController::class, 'getRooms']);

// Cities - Get unique cities from theaters
Route::get('/cities', function () {
    return Theater::distinct()
        ->orderBy('theater_city')
        ->pluck('theater_city')
        ->values();
});

// Showtimes - Full CRUD with filters
Route::get('/showtimes', [ShowtimeController::class, 'index']);
Route::post('/showtimes', [ShowtimeController::class, 'store']);
Route::get('/showtimes/{id}', [ShowtimeController::class, 'show']);
Route::put('/showtimes/{id}', [ShowtimeController::class, 'update']);
Route::delete('/showtimes/{id}', [ShowtimeController::class, 'destroy']);

// Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// ========== PROTECTED ROUTES (Require Authentication) ==========
Route::middleware('auth:sanctum')->group(function () {
    // User Profile
    Route::get('/user-profile', [UserController::class, 'profile']);
    Route::patch('/user-profile', [UserController::class, 'updateProfile']);
    Route::patch('/user-profile/password', [UserController::class, 'changePassword']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::resource('foods', FoodController::class)->except(['create', 'edit']);
Route::resource('seat-types', SeatTypeController::class)->except(['create', 'edit']);
Route::resource('time-slot-modifiers', TimeSlotModifierController::class)->except(['create', 'edit']);
Route::resource('day-modifiers', DayModifierController::class)->except(['create', 'edit']);

// Rooms API
Route::get('/theaters/{theater_id}/rooms', [RoomController::class, 'index']);
Route::post('/rooms', [RoomController::class, 'store']);
Route::put('/rooms/{id}', [RoomController::class, 'update']);
Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);

// Seats API
Route::get('/rooms/{room_id}/seats', [SeatController::class, 'index']);
Route::post('/seats', [SeatController::class, 'store']);
Route::delete('/seats/{id}', [SeatController::class, 'destroy']);
