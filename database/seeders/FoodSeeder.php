<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $foodsData = [
            [
                'food_name' => 'Popcorn',
                'description' => 'Bắp rang bơ cỡ lớn',
                'base_price' => 2.00,
                'status' => 'AVAILABLE',
            ],
            [
                'food_name' => 'Soda',
                'description' => 'Nước ngọt có ga (Coca, Pepsi...)',
                'base_price' => 1.20,
                'status' => 'AVAILABLE',
            ],
            [
                'food_name' => 'Hotdog',
                'description' => 'Bánh mì kẹp xúc xích nóng',
                'base_price' => 1.60,
                'status' => 'AVAILABLE',
            ],
            [
                'food_name' => 'Combo Popcorn + Soda',
                'description' => 'Combo tiết kiệm: Bắp và nước',
                'base_price' => 2.80,
                'status' => 'AVAILABLE',
            ],
        ];

        foreach ($foodsData as $item) {
            DB::table('foods')->updateOrInsert(
                // 1. Điều kiện để kiểm tra (cột unique)
                ['food_name' => $item['food_name']],
                
                // 2. Dữ liệu để chèn hoặc cập nhật
                [
                    'description' => $item['description'],
                    'base_price' => $item['base_price'],
                    'status' => $item['status'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );

        }
    }
}