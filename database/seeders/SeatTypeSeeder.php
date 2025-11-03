<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeatTypeSeeder extends Seeder
{
    public function run()
    {
        DB::table('seat_types')->insert([
            ['seat_type_id' => 'STD', 'seat_type_name' => 'Standard', 'seat_type_price' => 80000],
            ['seat_type_id' => 'GLD', 'seat_type_name' => 'Gold', 'seat_type_price' => 100000],
            ['seat_type_id' => 'PLT', 'seat_type_name' => 'Platinum', 'seat_type_price' => 120000],
            ['seat_type_id' => 'BOX', 'seat_type_name' => 'Box (Couple)', 'seat_type_price' => 160000],
        ]);
    }
}
