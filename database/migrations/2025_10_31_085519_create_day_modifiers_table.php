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
        Schema::create('day_modifiers', function (Blueprint $table) {
            $table->string('day_modifier_id', 10)->primary()->check('LENGTH(day_modifier_id) >= 1');
            $table->string('day_type', 20)->check('LENGTH(day_type) >= 1');
            $table->string('modifier_type', 15)->check('LENGTH(modifier_type) >= 1');
            $table->decimal('modifier_amount', 12, 2)->check('modifier_amount >= 0');
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
        Schema::dropIfExists('day_modifier');
    }
};
