<?php

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShowtimeController;
use App\Http\Controllers\MovieController;

Route::get('/movies', function () {
    return Movie::with('genres')->orderBy('release_date', 'desc')->limit(60)->get();
});

Route::get('/movies/{id}', [MovieController::class, 'show']);
Route::get('/movies/{id}/showtimes', [ShowtimeController::class, 'getShowtimesByMovie']);
