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
use App\Http\Controllers\BookingController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\SeatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========== PUBLIC ROUTES ==========
// Genres
Route::get('/genres', [GenreController::class, 'index']);

// Movies
Route::get('/movies', function () {
    return Movie::with('genres')->orderBy('release_date', 'desc')->limit(60)->get();
});
Route::get('/movies/{id}', [MovieController::class, 'show']);
Route::post('/movies', [MovieController::class, 'store']);
Route::put('/movies/{id}', [MovieController::class, 'update']);
Route::delete('/movies/{id}', [MovieController::class, 'destroy']);
Route::get('/movies/{id}/showtimes', [ShowtimeController::class, 'getShowtimesByMovie']);

// Theaters
Route::apiResource('theaters', TheaterController::class);
Route::get('/theaters/{id}/rooms', [TheaterController::class, 'getRooms']);
Route::get('/showtimes-by-theater', [ShowtimeController::class, 'getShowtimesForTheaterPage']);

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
    // Check all pending bookings for user
    Route::get('/bookings/check-pending-all', [BookingController::class, 'checkPendingAll']);

    // Bookings
    Route::post('/bookings/create', [BookingController::class, 'createBooking']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // 2. TẠO BOOKING (Bước 1: Giữ ghế / Lock Seats)
    Route::post('/bookings/hold', [BookingController::class, 'holdSeats']);

    // 3. CẬP NHẬT BOOKING (Bước 2: Thêm đồ ăn)
    Route::put('/bookings/{booking_id}/foods', [BookingController::class, 'addFoods']);

    // 4. THANH TOÁN (Bước 3: Finalize)
    Route::post('/bookings/finalize', [BookingController::class, 'finalizePayment']);
    // Kiểm tra trạng thái booking đang giữ
    Route::post('/bookings/check-pending', [BookingController::class, 'checkPendingBooking']);
    // Hủy booking đang giữ
    Route::post('/bookings/cancel', [BookingController::class, 'cancelBooking']);
    // Cập nhật ghế trong booking đang giữ
    Route::put('/bookings/update-seats', [BookingController::class, 'updateSeats']);
    // 5. XÁC THỰC BOOKING (Validate booking)
    Route::get('/bookings/{booking_id}/validate', [BookingController::class, 'validateBooking']);
    // Lấy vé cho booking
    Route::get('/bookings/{booking_id}/tickets', [BookingController::class, 'getBookingTickets']);
    // Get user bookings
    Route::get('/user/bookings', [BookingController::class, 'getUserBookings']);
});
Route::resource('foods', FoodController::class)->except(['create', 'edit']);
Route::resource('seat-types', SeatTypeController::class)->except(['create', 'edit']);
Route::resource('time-slot-modifiers', TimeSlotModifierController::class)->except(['create', 'edit']);
Route::resource('day-modifiers', DayModifierController::class)->except(['create', 'edit']);

Route::post('/bookings', [BookingController::class,'create']);

// 1. Lấy ghế đã bán (bao gồm cả ghế đang được giữ)
Route::get('/showtimes/{showtime_id}/sold-seats', [BookingController::class, 'getSoldSeats']);

// Rooms API
Route::get('/theaters/{theater_id}/rooms', [RoomController::class, 'index']);
Route::post('/rooms', [RoomController::class, 'store']);
Route::put('/rooms/{id}', [RoomController::class, 'update']);
Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);

// Seats API
Route::get('/rooms/{room_id}/seats', [SeatController::class, 'index']);
Route::post('/seats', [SeatController::class, 'store']);
Route::delete('/seats/{id}', [SeatController::class, 'destroy']);
