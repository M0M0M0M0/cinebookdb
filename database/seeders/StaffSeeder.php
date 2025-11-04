<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class StaffSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('staffs')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $faker = Faker::create();

        $staffs = [];

        for ($i = 0; $i < 10; $i++) {
            $staffs[] = [
                'staff_id' => Str::uuid(),
                'full_name' => $faker->name(),
                'date_of_birth' => $faker->date('Y-m-d', '1995-01-01'),
                'address' => $faker->address(),
                'phone_number' => $faker->phoneNumber(),
                'email' => $faker->unique()->companyEmail(),
                'password_hash' => Hash::make('admin123'),
                'role_id' => 'R002',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('staffs')->insert($staffs);
    }
}
