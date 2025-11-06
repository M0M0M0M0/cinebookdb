<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TheaterSeeder extends Seeder
{
    public function run()
    {
        $theaters = [
            // --- Hanoi ---
            [
                'theater_name' => 'CGV Vincom Ba Trieu',
                'theater_address' => 'Vincom Center, 191 Ba Trieu, Hai Ba Trung, Hanoi',
                'theater_city' => 'Hanoi',
            ],
            [
                'theater_name' => 'Lotte Cinema Keangnam',
                'theater_address' => '72nd Floor, Keangnam Landmark, Pham Hung, Nam Tu Liem, Hanoi',
                'theater_city' => 'Hanoi',
            ],
            [
                'theater_name' => 'Beta Cineplex Thanh Xuan',
                'theater_address' => '41 Nguyen Thi Dinh, Thanh Xuan, Hanoi',
                'theater_city' => 'Hanoi',
            ],
            [
                'theater_name' => 'Galaxy Cinema Mipec Long Bien',
                'theater_address' => 'Mipec Riverside, 2 Long Bien II, Long Bien, Hanoi',
                'theater_city' => 'Hanoi',
            ],
            [
                'theater_name' => 'BHD Star Pham Ngoc Thach',
                'theater_address' => '8th Floor, Vincom Center, 2 Pham Ngoc Thach, Dong Da, Hanoi',
                'theater_city' => 'Hanoi',
            ],

            // --- Ho Chi Minh City ---
            [
                'theater_name' => 'CGV Crescent Mall',
                'theater_address' => '101 Ton Dat Tien, Tan Phu, District 7, Ho Chi Minh City',
                'theater_city' => 'Ho Chi Minh City',
            ],
            [
                'theater_name' => 'Galaxy Nguyen Du',
                'theater_address' => '116 Nguyen Du, District 1, Ho Chi Minh City',
                'theater_city' => 'Ho Chi Minh City',
            ],
            [
                'theater_name' => 'BHD Star Bitexco',
                'theater_address' => '36 Ho Tung Mau, District 1, Ho Chi Minh City',
                'theater_city' => 'Ho Chi Minh City',
            ],
            [
                'theater_name' => 'Lotte Cinema Nowzone',
                'theater_address' => '235 Nguyen Van Cu, District 1, Ho Chi Minh City',
                'theater_city' => 'Ho Chi Minh City',
            ],
            [
                'theater_name' => 'Mega GS Cao Thang',
                'theater_address' => '19 Cao Thang, District 3, Ho Chi Minh City',
                'theater_city' => 'Ho Chi Minh City',
            ],

            // --- Da Nang ---
            [
                'theater_name' => 'CGV Vincom Da Nang',
                'theater_address' => 'Vincom Plaza, 910A Ngo Quyen, Son Tra, Da Nang',
                'theater_city' => 'Da Nang',
            ],
            [
                'theater_name' => 'Lotte Cinema Da Nang',
                'theater_address' => '6th Floor, Lotte Mart, 6 Ngu Hanh Son, Hai Chau, Da Nang',
                'theater_city' => 'Da Nang',
            ],
            [
                'theater_name' => 'Galaxy Cinema Da Nang',
                'theater_address' => '478 Dien Bien Phu, Thanh Khe, Da Nang',
                'theater_city' => 'Da Nang',
            ],
            [
                'theater_name' => 'Beta Cineplex Da Nang',
                'theater_address' => '2 Vo Nguyen Giap, Son Tra, Da Nang',
                'theater_city' => 'Da Nang',
            ],
            [
                'theater_name' => 'Starlight Da Nang',
                'theater_address' => 'TTC Plaza, 254 Nguyen Van Linh, Thanh Khe, Da Nang',
                'theater_city' => 'Da Nang',
            ],
        ];

        foreach ($theaters as &$theater) {
            $theater['created_at'] = now();
            $theater['updated_at'] = now();
        }

        DB::table('theaters')->insert($theaters);
    }
}
