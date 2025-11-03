<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Showtime;

class ShowtimeController extends Controller
{
    // Lấy danh sách showtime theo movie
    public function getShowtimesByMovie($id)
    {
        $showtimes = Showtime::where('movie_id', $id)
            ->with('room.theater') // lấy thông tin room và theater
            ->where('status', 'active')
            ->get();

        return response()->json($showtimes);
    }
}
