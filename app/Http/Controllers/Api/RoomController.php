<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Seat;

class RoomController extends Controller
{
    // Get all rooms for a theater
    public function index($theater_id)
    {
        $rooms = Room::where('theater_id', $theater_id)
                     ->with('seats')
                     ->get();

        return response()->json($rooms);
    }

    // Create a new room
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_name' => 'required|string|max:100',
            'room_type' => 'required|string|max:30',
            'theater_id' => 'required|exists:theaters,theater_id'
        ]);

        $room = Room::create($validated);
        return response()->json($room, 201);
    }

    // Update a room
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        $room->update($request->only(['room_name', 'room_type']));
        return response()->json($room);
    }

    // Delete a room and its seats
    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $room->seats()->delete();
        $room->delete();

        return response()->json(['message' => 'Room deleted successfully']);
    }
}
