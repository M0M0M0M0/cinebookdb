<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Theater;

class TheaterController extends Controller
{
    /**
     * Display a listing of theaters (optionally with rooms & seats)
     */
    public function index(Request $request)
    {
        // If frontend sends ?_with=rooms, load rooms + seats for dashboard
        if ($request->has('_with') && $request->_with === 'rooms') {
            $theaters = Theater::with('rooms.seats')->orderBy('theater_id', 'desc')->get();
        } else {
            $theaters = Theater::orderBy('theater_id', 'desc')->get();
        }

        // Add derived room + seat counts
        $theaters->transform(function ($theater) {
            $theater->room_count = $theater->rooms->count() ?? 0;
            $theater->seat_capacity = $theater->rooms->sum(function ($room) {
                return $room->seats->count() ?? 0;
            });
            return $theater;
        });

        return response()->json($theaters);
    }
    /**
 * ✅ Update an existing theater
 */
    public function update(Request $req, $id)
    {
        $theater = Theater::findOrFail($id);

        $data = $req->validate([
            'theater_name'    => 'required|string|max:100',
            'theater_address' => 'required|string|max:255',
            'theater_city'    => 'required|string|max:100',
        ]);

        $theater->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Theater updated successfully',
            'theater' => $theater
        ]);
    }

    /**
     * Store a new theater
     */
    public function store(Request $req)
    {
        $data = $req->validate([
            'theater_name'    => 'required|string|max:100',
            'theater_address' => 'required|string|max:255',
            'theater_city'    => 'required|string|max:100',
        ]);

        $theater = Theater::create($data);
        return response()->json($theater, 201);
    }

    /**
     * Show one theater (with rooms + seats)
     */
    public function show($id)
    {
        $theater = Theater::with('rooms.seats')->findOrFail($id);
        $theater->room_count = $theater->rooms->count();
        $theater->seat_capacity = $theater->rooms->sum(fn ($r) => $r->seats->count());
        return response()->json($theater);
    }

    /**
     * Delete a theater and its related rooms & seats
     */
    public function destroy($id)
    {
        $theater = Theater::with('rooms.seats')->findOrFail($id);

        // Optional: delete child data first to maintain referential integrity
        foreach ($theater->rooms as $room) {
            $room->seats()->delete();
            $room->delete();
        }

        $theater->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Return all rooms for a given theater (with their seats)
     */
    public function getRooms($id)
    {
        $theater = Theater::with('rooms.seats')->find($id);

        if (!$theater) {
            return response()->json(['error' => 'Theater not found'], 404);
        }

        return response()->json($theater->rooms);
    }
}
