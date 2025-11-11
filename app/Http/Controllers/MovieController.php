<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MovieController extends Controller
{
    /**
     * Display a listing of movies
     */
    public function index()
    {
        $movies = Movie::with('genres')->get();
        return response()->json($movies);
    }

    /**
     * Display the specified movie
     */
    public function show($id)
    {
        $movie = Movie::with('genres')->find($id);

        if (!$movie) {
            return response()->json(['error' => 'Movie not found'], 404);
        }

        return response()->json($movie);
    }

    /**
     * Store a newly created movie
     */
    public function store(Request $request)
    {
        // Debug: Log incoming request
        \Log::info('Movie Store Request', [
            'data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'movie_id' => 'required|integer|unique:movies,movie_id',
            'title' => 'required|string|max:255',
            'duration' => 'required|integer|min:1|max:600',
            'poster_path' => 'required|string',
            'overview' => 'required|string|max:2000',
            'release_date' => 'required|date',
            'original_title' => 'nullable|string|max:255',
            'original_language' => 'nullable|string|min:2|max:10',
            'backdrop_path' => 'nullable|string',
            'trailer_link' => 'nullable|string|url',
            'vote_average' => 'nullable|numeric|min:0|max:10',
            'genres' => 'nullable|array',
            'genres.*' => 'integer|exists:genres,genre_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // ✅ FIX: Nếu original_title không có, dùng title
            $originalTitle = $request->original_title;
            if (empty($originalTitle)) {
                $originalTitle = $request->title;
            }

            // Create movie without genres
            $movie = Movie::create([
                'movie_id' => $request->movie_id,
                'title' => $request->title,
                'original_title' => $originalTitle, // ✅ Sử dụng giá trị đã xử lý
                'original_language' => $request->original_language,
                'duration' => $request->duration,
                'poster_path' => $request->poster_path,
                'backdrop_path' => $request->backdrop_path,
                'trailer_link' => $request->trailer_link,
                'overview' => $request->overview,
                'release_date' => $request->release_date,
                'vote_average' => $request->vote_average,
            ]);

            // Sync genres in movie_genre pivot table
            if ($request->has('genres') && is_array($request->genres)) {
                $movie->genres()->sync($request->genres);
            }

            DB::commit();

            return response()->json([
                'message' => 'Movie created successfully',
                'data' => $movie->load('genres')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create movie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified movie
     */
    public function update(Request $request, $id)
    {
        $movie = Movie::find($id);

        if (!$movie) {
            return response()->json(['error' => 'Movie not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'duration' => 'sometimes|integer|min:1|max:600',
            'poster_path' => 'sometimes|string',
            'overview' => 'sometimes|string|max:2000',
            'release_date' => 'sometimes|date',
            'original_title' => 'nullable|string|max:255',
            'original_language' => 'nullable|string|min:2|max:10',
            'backdrop_path' => 'nullable|string',
            'trailer_link' => 'nullable|string|url',
            'vote_average' => 'nullable|numeric|min:0|max:10',
            'genres' => 'nullable|array',
            'genres.*' => 'integer|exists:genres,genre_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update movie fields
            $movie->update($request->only([
                'title',
                'original_title',
                'original_language',
                'duration',
                'poster_path',
                'backdrop_path',
                'trailer_link',
                'overview',
                'release_date',
                'vote_average'
            ]));

            // Sync genres in movie_genre pivot table
            if ($request->has('genres')) {
                if (is_array($request->genres) && count($request->genres) > 0) {
                    $movie->genres()->sync($request->genres);
                } else {
                    // If empty array, detach all genres
                    $movie->genres()->detach();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Movie updated successfully',
                'data' => $movie->load('genres')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update movie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified movie
     */
    public function destroy($id)
    {
        $movie = Movie::find($id);

        if (!$movie) {
            return response()->json(['error' => 'Movie not found'], 404);
        }

        DB::beginTransaction();
        try {
            // Detach all genres first (cascade will handle this, but explicit is better)
            $movie->genres()->detach();

            // Delete movie
            $movie->delete();

            DB::commit();

            return response()->json(['message' => 'Movie deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete movie',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}