<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Temporarily disable FK checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('roles')->insert([
            [
                'role_id' => 'R001',
                'role_name' => 'User',
                'role_description' => 'Regular customer using CineBook to book tickets.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 'R002',
                'role_name' => 'Admin',
                'role_description' => 'Administrative staff with full system access.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
