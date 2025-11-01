<?php

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\MovieGenreController;

Route::get('/movies', function () {
    return Movie::with('genres')->orderBy('release_date', 'desc')->limit(60)->get();
});
Route::get('/genres', [GenreController::class, 'index']);
Route::get('/genres/{genre}', [MovieGenreController::class, 'moviesByGenre']);
