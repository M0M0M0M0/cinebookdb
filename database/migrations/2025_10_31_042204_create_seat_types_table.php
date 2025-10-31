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
        Schema::create('seat_types', function (Blueprint $table) {
            $table->string('seat_type_id', 10)->primary()->check('LENGTH(seat_type_id) >= 1');
            $table->string('seat_type_name', 50)->check('LENGTH(seat_type_name) >= 1');
            $table->decimal('seat_type_price', 12, 2)->check('seat_type_price >= 0');
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
        Schema::dropIfExists('seat_types');
    }
};
