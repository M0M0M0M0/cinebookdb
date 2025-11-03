<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeatSeeder extends Seeder
{
    public function run()
    {
        $rooms = DB::table('rooms')->pluck('room_id');
        $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        foreach ($rooms as $roomId) {
            $seats = [];

            foreach ($rows as $row) {
                for ($num = 1; $num <= 16; $num++) {
                    $type = $this->getSeatType($row, $num);
                    $seats[] = [
                        'seat_row' => $row,
                        'seat_number' => $num,
                        'seat_type_id' => $type,
                        'room_id' => $roomId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            DB::table('seats')->insert($seats);
        }
    }

    private function getSeatType($row, $num)
    {
        switch ($row) {
            case 'A':
                return 'STD';
            case 'B':
                return in_array($num, [1, 2, 15, 16]) ? 'STD' : 'GLD';
            case 'C':
            case 'D':
            case 'E':
                return in_array($num, [1, 2, 15, 16]) ? 'GLD' : 'PLT';
            case 'F':
                return 'GLD';
            case 'G':
                return 'BOX';
            default:
                return 'STD';
        }
    }
}
