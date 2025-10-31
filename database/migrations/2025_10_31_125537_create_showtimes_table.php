<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id('showtime_id');
            $table->integer('movie_id');
            $table->unsignedBigInteger('room_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->decimal('base_price', 12, 2);
            $table->string('status', 50);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE showtimes ADD CONSTRAINT CHK_Showtime_BasePrice CHECK (base_price >= 0)");
        DB::statement("ALTER TABLE showtimes ADD CONSTRAINT CHK_Showtime_StartEnd CHECK (end_time > start_time)");


        Schema::table('showtimes', function (Blueprint $table) {
            $table->foreign('movie_id')->references('movie_id')->on('movies')->onDelete('restrict');
            $table->foreign('room_id')->references('room_id')->on('rooms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('showtimes');
    }
};
