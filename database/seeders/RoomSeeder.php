<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run()
    {
        $rooms = [];
        $theaters = DB::table('theaters')->pluck('theater_id');

        foreach ($theaters as $theaterId) {
            for ($i = 1; $i <= 20; $i++) {
                $rooms[] = [
                    'room_name' => "Room {$i}",
                    'room_type' => 'Standard',
                    'theater_id' => $theaterId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('rooms')->insert($rooms);
    }
}
