<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class CacController extends Controller
{
    /**
     * Lấy actors và director của một phim
     */
    public function getMovieCredits($movieId)
    {
        try {
            $movie = Movie::find($movieId);

            if (!$movie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Movie not found'
                ], 404);
            }

            // Lấy actors (cast)
            $actors = $movie->casts()->get()->map(function ($person) {
                return [
                    'cac_id' => $person->cac_id,
                    'tmdb_id' => $person->tmdb_id,
                    'name' => $person->name,
                    'character' => $person->pivot->character,
                    'profile_path' => $person->profile_path,
                    'cast_order' => $person->pivot->cast_order,
                ];
            });

            // Lấy director từ crew
            $directors = $movie->crews()
                ->wherePivot('job', 'Director')
                ->get()
                ->map(function ($person) {
                    return [
                        'cac_id' => $person->cac_id,
                        'tmdb_id' => $person->tmdb_id,
                        'name' => $person->name,
                        'profile_path' => $person->profile_path,
                        'job' => $person->pivot->job,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'actors' => $actors,
                    'directors' => $directors,
                    'actors_count' => $actors->count(),
                    'directors_count' => $directors->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching credits',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
