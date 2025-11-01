<?php

namespace App\Http\Controllers;

use App\Services\TmdbService;

class MovieController extends Controller
{
    protected $tmdb;

    public function __construct(TmdbService $tmdb)
    {
        $this->tmdb = $tmdb;
    }

    // test function
    public function show($id)
    {
        $movie = $this->tmdb->getMovie($id);
        return response()->json($movie);
    }
}
