<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_slot_modifiers', function (Blueprint $table) {
            $table->string('time_slot_modifier_id', 10)->primary()->check('LENGTH(time_slot_modifier_id) >= 1');
            $table->string('time_slot_name', 255)->check('LENGTH(time_slot_name) >= 1');
            $table->time('ts_start_time', 3);
            $table->time('ts_end_time', 3);
            $table->string('modifier_type', 15)->check('LENGTH(modifier_type) >= 1');
            $table->decimal('ts_amount', 12, 2)->check('ts_amount >= 0');
            $table->string('operation', 20)->check('LENGTH(operation) >= 1');
            $table->boolean('is_active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_slot_modifiers');
    }
};
