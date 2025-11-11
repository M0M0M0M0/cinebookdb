<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Seat;

class SeatController extends Controller
{
    // Get all seats for a room
    public function index($room_id)
{
    $seats = \App\Models\Seat::where('room_id', $room_id)
        ->orderBy('seat_row')
        ->orderBy('seat_number')
        ->get();

    return response()->json($seats);
}


    // Create new seat
    public function store(Request $request)
    {
        $validated = $request->validate([
            'seat_row' => 'required|string|max:1',
            'seat_number' => 'required|integer|min:1',
            'seat_type_id' => 'required|string|max:10',
            'room_id' => 'required|exists:rooms,room_id'
        ]);

        $seat = Seat::create($validated);
        return response()->json($seat, 201);
    }

    // Delete seat
    public function destroy($id)
    {
        $seat = Seat::findOrFail($id);
        $seat->delete();
        return response()->json(['message' => 'Seat deleted successfully']);
    }

    // Bulk insert seats (array of seats)
    public function bulkStore(Request $request)
    {
        $seats = $request->input('seats', []);
        if (!is_array($seats) || empty($seats)) {
            return response()->json(['error' => 'Invalid or empty seats array'], 400);
        }

        $inserted = [];
        foreach ($seats as $seatData) {
            $validated = validator($seatData, [
                'seat_row' => 'required|string|max:1',
                'seat_number' => 'required|integer|min:1',
                'seat_type_id' => 'required|string|max:10',
                'room_id' => 'required|exists:rooms,room_id',
            ])->validate();

            $inserted[] = Seat::create($validated);
        }

        return response()->json([
            'success' => true,
            'count' => count($inserted),
            'data' => $inserted
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'seat_row' => 'required|string|max:1',
            'seat_number' => 'required|integer|min:1',
            'seat_type_id' => 'required|string|exists:seat_types,seat_type_id',
            'room_id' => 'required|integer|exists:rooms,room_id',
        ]);

        $seat = \App\Models\Seat::findOrFail($id);
        $seat->update($validated);

        return response()->json($seat);
    }


}
