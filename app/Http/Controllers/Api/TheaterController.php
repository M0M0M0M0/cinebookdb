<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Theater;
use Illuminate\Http\Request;

class TheaterController extends Controller
{
    public function index()
    {
        return Theater::orderBy('theater_id', 'desc')->get();
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'theater_name'    => 'required|string|max:100',
            'theater_address' => 'required|string|max:255',
            'theater_city'    => 'required|string|max:100',
        ]);

        $t = Theater::create($data);
        return response()->json($t, 201);
    }

    public function show($id)
    {
        return Theater::with('rooms')->findOrFail($id);
    }

    public function destroy($id)
    {
        Theater::findOrFail($id)->delete();
        return response()->json(['deleted' => true]);
    }
    public function getRooms($id)
    {
        $theater = Theater::find($id);

        if (!$theater) {
            return response()->json(['error' => 'Theater not found'], 404);
        }

        $rooms = $theater->rooms()->orderBy('room_name')->get();
        return response()->json($rooms);
    }
}
