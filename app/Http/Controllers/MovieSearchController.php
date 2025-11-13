<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;

class MovieSearchController extends Controller
{
    public function searchByCac(Request $request)
    {
        $query = $request->get('query', '');

        if (!$query) {
            return response()->json([
                'error' => 'Query parameter is required'
            ], 400);
        }

        // Lấy tất cả movie mà có cac name giống query
        $movies = Movie::whereHas('cacs', function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%");
        })
        ->with(['cacs' => function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%");
        }])
        ->get();

        return response()->json([
            'count' => $movies->count(),
            'movies' => $movies
        ]);
    }
}
