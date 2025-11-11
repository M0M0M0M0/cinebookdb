<?php

// app/Http/Controllers/ShowtimeController.php

namespace App\Http\Controllers;

use App\Models\Showtime;
use App\Models\Movie;
use App\Models\Theater;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ShowtimeController extends Controller
{
    /**
     * Display a listing of showtimes with filters
     */
    public function index(Request $request)
    {
        $query = Showtime::with(['movie', 'room.theater']);

        // Apply filters
        if ($request->has('movie_id')) {
            $query->byMovie($request->movie_id);
        }

        if ($request->has('theater_id')) {
            $query->byTheater($request->theater_id);
        }

        if ($request->has('room_id')) {
            $query->byRoom($request->room_id);
        }

        if ($request->has('date')) {
            $query->byDate($request->date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Order by date and time
        $query->orderBy('start_time', 'asc');

        $showtimes = $query->get();

        // Format for frontend
        $formatted = $showtimes->map(function ($showtime) {
            return [
                'showtime_id' => $showtime->showtime_id,
                'movie_id' => $showtime->movie_id,
                'room_id' => $showtime->room_id,
                'theater_id' => $showtime->room->theater_id,
                'show_date' => $showtime->start_time->format('Y-m-d'),
                'show_time' => $showtime->start_time->format('H:i:s'),
                'start_time' => $showtime->start_time->format('Y-m-d H:i:s'),
                'end_time' => $showtime->end_time->format('Y-m-d H:i:s'),
                'price' => $showtime->base_price,
                'base_price' => $showtime->base_price,
                'status' => $showtime->status,
                'created_at' => $showtime->created_at,
                'updated_at' => $showtime->updated_at,
            ];
        });

        return response()->json($formatted);
    }

    /**
     * Store a newly created showtime
     */
    public function store(Request $request)
    {
        // First validate the basic format
        $validator = Validator::make($request->all(), [
            'movie_id' => 'required|exists:movies,movie_id',
            'room_id' => 'required|exists:rooms,room_id',
            'start_time' => 'required|string',
            'price' => 'required|numeric|min:0',
            'status' => 'in:Available,Full,Cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Try to parse the datetime
        try {
            $startDateTime = Carbon::parse($request->start_time);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['start_time' => ['Invalid datetime format']]], 422);
        }

        // Check if the datetime is in the future
        if ($startDateTime->isPast()) {
            return response()->json(['errors' => ['start_time' => ['The start time must be a future date']]], 422);
        }

        // Get movie duration to calculate end_time
        $movie = Movie::find($request->movie_id);
        if (!$movie || !$movie->duration) {
            return response()->json(['error' => 'Movie duration not found'], 400);
        }

        $endDateTime = $startDateTime->copy()->addMinutes($movie->duration + 15); // +15 min for cleanup

        // Check for conflicts
        $conflict = Showtime::where('room_id', $request->room_id)
            ->where(function ($query) use ($startDateTime, $endDateTime) {
                $query->whereBetween('start_time', [$startDateTime, $endDateTime])
                      ->orWhereBetween('end_time', [$startDateTime, $endDateTime])
                      ->orWhere(function ($q) use ($startDateTime, $endDateTime) {
                          $q->where('start_time', '<=', $startDateTime)
                            ->where('end_time', '>=', $endDateTime);
                      });
            })
            ->exists();

        if ($conflict) {
            return response()->json(['error' => 'Time slot conflicts with existing showtime'], 409);
        }

        $showtime = Showtime::create([
            'movie_id' => $request->movie_id,
            'room_id' => $request->room_id,
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'base_price' => $request->price,
            'status' => $request->status ?? 'Available'
        ]);

        return response()->json($showtime->load(['movie', 'room.theater']), 201);
    }

    /**
     * Display the specified showtime
     */
    public function show($id)
    {
        $showtime = Showtime::with(['movie', 'room.theater'])->find($id);

        if (!$showtime) {
            return response()->json(['error' => 'Showtime not found'], 404);
        }

        return response()->json($showtime);
    }

    /**
     * Update the specified showtime
     */
    public function update(Request $request, $id)
    {
        $showtime = Showtime::find($id);

        if (!$showtime) {
            return response()->json(['error' => 'Showtime not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'movie_id' => 'sometimes|exists:movies,movie_id',
            'room_id' => 'sometimes|exists:rooms,room_id',
            'start_time' => 'sometimes|date',
            'price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:Available,Full,Cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update start_time if provided
        if ($request->has('start_time')) {
            $startDateTime = Carbon::parse($request->start_time);

            // Recalculate end_time
            $movieId = $request->movie_id ?? $showtime->movie_id;
            $movie = Movie::find($movieId);
            $endDateTime = $startDateTime->copy()->addMinutes($movie->duration + 15);

            $showtime->start_time = $startDateTime;
            $showtime->end_time = $endDateTime;
        }

        if ($request->has('movie_id')) {
            $showtime->movie_id = $request->movie_id;
        }

        if ($request->has('room_id')) {
            $showtime->room_id = $request->room_id;
        }

        if ($request->has('price')) {
            $showtime->base_price = $request->price;
        }

        if ($request->has('status')) {
            $showtime->status = $request->status;
        }

        $showtime->save();

        return response()->json($showtime->load(['movie', 'room.theater']));
    }

    /**
     * Remove the specified showtime
     */
    public function destroy($id)
    {
        $showtime = Showtime::find($id);

        if (!$showtime) {
            return response()->json(['error' => 'Showtime not found'], 404);
        }

        $showtime->delete();

        return response()->json(['message' => 'Showtime deleted successfully']);
    }

    public function getShowtimesByMovie($movieId, Request $request)
    {
        // Check if movie exists
        $movie = Movie::find($movieId);
        if (!$movie) {
            return response()->json(['error' => 'Movie not found'], 404);
        }

        $query = Showtime::with(['movie', 'room.theater'])
            ->byMovie($movieId);

        // Apply additional filters if provided
        if ($request->has('theater_id')) {
            $query->byTheater($request->theater_id);
        }

        if ($request->has('date')) {
            $query->byDate($request->date);
        } else {
            // Default: only show future showtimes
            $query->where('start_time', '>=', Carbon::now());
        }

        if ($request->has('city')) {
            $query->whereHas('room.theater', function ($q) use ($request) {
                $q->where('theater_city', $request->city);
            });
        }

        // Order by date and time
        $query->orderBy('start_time', 'asc');

        $showtimes = $query->get();

        // Group by date and theater for better frontend handling
        $formatted = $showtimes->groupBy(function ($showtime) {
            return $showtime->start_time->format('Y-m-d');
        })->map(function ($dateGroup) {
            return $dateGroup->groupBy(function ($showtime) {
                return $showtime->room->theater->theater_id;
            })->map(function ($theaterGroup) {
                $theater = $theaterGroup->first()->room->theater;
                return [
                    'theater_id' => $theater->theater_id,
                    'theater_name' => $theater->theater_name,
                    'theater_address' => $theater->theater_address,
                    'theater_city' => $theater->theater_city,
                    'showtimes' => $theaterGroup->map(function ($showtime) {
                        return [
                            'showtime_id' => $showtime->showtime_id,
                            'room_id' => $showtime->room_id,
                            'room_name' => $showtime->room->room_name,
                            'start_time' => $showtime->start_time->format('H:i'),
                            'end_time' => $showtime->end_time->format('H:i'),
                            'base_price' => $showtime->base_price,
                            'status' => $showtime->status,
                            'available_seats' => $showtime->room->seat_capacity
                        ];
                    })->values()
                ];
            })->values();
        });

        return response()->json([
            'movie_id' => $movieId,
            'movie_title' => $movie->title,
            'showtimes_by_date' => $formatted
        ]);
    }

    public function getShowtimesForTheaterPage()
    {
        $theaters = \App\Models\Theater::whereHas('showtimes', function ($query) {
            $query->available()->where('start_time', '>=', now());
        })
        ->with([
            'showtimes' => function ($query) {
                $query->available()
                      ->where('start_time', '>=', now())
                      ->orderBy('start_time');
            },
            'showtimes.movie'
        ])
        ->get();

        $formattedData = $theaters->map(function ($theater) {
            $showtimesByMovie = $theater->showtimes->groupBy('movie_id');

            $movies = $showtimesByMovie->map(function ($movieShowtimes) {
                $movie = $movieShowtimes->first()->movie;

                if (!$movie) {
                    return null;
                }

                $showtimesByDate = $movieShowtimes->groupBy(function ($showtime) {
                    return $showtime->start_time->format('Y-m-d');
                });

                $formattedShowtimes = $showtimesByDate->map(function ($dayShowtimes, $date) {
                    return [
                        'date' => $date,
                        'times' => $dayShowtimes->map(function ($showtime) {
                            return $showtime->start_time->format('H:i');
                        })->unique()->values(),
                    ];
                })->values();

                return [
                    'id' => $movie->movie_id,
                    'title' => $movie->title,
                    'poster' => $movie->poster,
                    'showtimes' => $formattedShowtimes,
                ];
            })->filter()->values();

            return [
                'id' => $theater->theater_id,
                'name' => $theater->theater_name,
                'address' => $theater->theater_address,
                'region' => $theater->theater_city,
                'movies' => $movies,
            ];
        });

        return response()->json($formattedData);
    }
}
