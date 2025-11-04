<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class WebUserSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('web_users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $faker = Faker::create();

        $users = [];

        for ($i = 0; $i < 200; $i++) {
            $users[] = [
                'web_user_id' => Str::uuid(),
                'full_name' => $faker->name(),
                'date_of_birth' => $faker->date('Y-m-d', '2005-01-01'),
                'address' => $faker->address(),
                'phone_number' => $faker->phoneNumber(),
                'email' => $faker->unique()->safeEmail(),
                'password_hash' => Hash::make('user123'),
                'role_id' => 'R001',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('web_users')->insert($users);
    }
}
