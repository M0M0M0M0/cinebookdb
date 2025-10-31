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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');
            $table->uuid('web_user_id');
            $table->unsignedBigInteger('showtime_id');
            $table->dateTime('booking_date');
            $table->string('status', 50);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE bookings ADD CONSTRAINT CHK_Booking_Status CHECK (status IN ('open','sold-out','cancelled','completed'))");
        

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('showtime_id')->references('showtime_id')->on('showtimes')->onDelete('restrict');
            $table->foreign('web_user_id')->references('web_user_id')->on('web_users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};
