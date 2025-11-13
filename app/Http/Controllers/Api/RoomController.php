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
        'theater_id' => 'required|integer|exists:theaters,theater_id',
    ]);

    $room = \App\Models\Room::create($validated);

    // Auto-generate seats
    $rows = range('A', 'G');
    $seatsPerRow = 16;

    foreach ($rows as $row) {
        for ($num = 1; $num <= $seatsPerRow; $num++) {
            $type = 'GLD';
            if ($row === 'H') $type = 'BOX';
            elseif ($num <= 2 || $num >= 15) $type = 'STD';
            elseif ($num >= 5 && $num <= 10) $type = 'PLT';

            \App\Models\Seat::create([
                'seat_row' => $row,
                'seat_number' => $num,
                'seat_type_id' => $type,
                'room_id' => $room->room_id,
            ]);
        }
    }

    $room->load(['seats']); // include seats if you want
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

    // Delete all seats for a specific room (for seat layout reset)
public function deleteSeats($room_id)
{
    $room = Room::findOrFail($room_id);
    $room->seats()->delete();

    return response()->json([
        'success' => true,
        'message' => "All seats deleted for room ID {$room_id}"
    ]);
}

}
