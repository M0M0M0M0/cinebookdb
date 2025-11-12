<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeatTypeSeeder extends Seeder
{
    public function run()
    {
        // Mảng dữ liệu các loại ghế
        $seatTypes = [
            ['seat_type_id' => 'STD', 'seat_type_name' => 'Standard', 'seat_type_price' => 80000],
            ['seat_type_id' => 'GLD', 'seat_type_name' => 'Gold', 'seat_type_price' => 100000],
            ['seat_type_id' => 'PLT', 'seat_type_name' => 'Platinum', 'seat_type_price' => 120000],
            ['seat_type_id' => 'BOX', 'seat_type_name' => 'Box (Couple)', 'seat_type_price' => 160000],
        ];

        // Lặp qua mảng và dùng updateOrInsert
        foreach ($seatTypes as $type) {
            DB::table('seat_types')->updateOrInsert(
                // Điều kiện để kiểm tra (dựa trên khóa chính)
                ['seat_type_id' => $type['seat_type_id']], 
                
                // Dữ liệu để chèn (nếu chưa có) hoặc cập nhật (nếu đã có)
                [
                    'seat_type_name' => $type['seat_type_name'],
                    'seat_type_price' => $type['seat_type_price']
                ] 
            );
        }
    }
}