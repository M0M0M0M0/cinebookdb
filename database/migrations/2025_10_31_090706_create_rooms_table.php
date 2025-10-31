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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id('room_id');
            $table->string('room_name', 100)->check('LENGTH(room_name) >= 1');
            $table->string('room_type', 30)->check('LENGTH(room_type) >= 1');
            $table->integer('theater_id');
            $table->timestamps();

            $table->foreign('theater_id')
                  ->references('theater_id')
                  ->on('theaters')
                  ->onDelete('restrict'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rooms');
    }
};
