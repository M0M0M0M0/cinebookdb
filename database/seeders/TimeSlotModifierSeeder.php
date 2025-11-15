<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeSlotModifierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('time_slot_modifiers')->insert([
            [
                'time_slot_modifier_id' => 'peak',
                'time_slot_name' => 'peak',
                'ts_start_time' => '15:00:00',
                'ts_end_time' => '23:59:59',
                'modifier_type' => 'fixed',
                'ts_amount' => 2.00,
                'operation' => 'increase',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'time_slot_modifier_id' => 'normal',
                'time_slot_name' => 'normal',
                'ts_start_time' => '00:00:01',
                'ts_end_time' => '14:59:59',
                'modifier_type' => 'fixed',
                'ts_amount' => 0.00,
                'operation' => 'increase',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
