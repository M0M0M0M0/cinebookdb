<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DayModifierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('day_modifiers')->insert([
            [
                'day_modifier_id' => 'wk',
                'day_type' => 'weekend',
                'modifier_type' => 'fixed',
                'modifier_amount' => 5.00,
                'operation' => 'increase',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'day_modifier_id' => 'wd',
                'day_type' => 'weekday',
                'modifier_type' => 'fixed',
                'modifier_amount' => 0.00,
                'operation' => 'increase',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
