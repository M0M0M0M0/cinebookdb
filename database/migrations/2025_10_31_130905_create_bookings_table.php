<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');

            // who booked
            $table->uuid('web_user_id');

            // which showtime
            $table->unsignedBigInteger('showtime_id');



            // open = đang pending / chờ thanh toán
            // completed = thanh toán xong
            // cancelled = user hủy hoặc expire
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');

            // snapshot seats (array JSON)
            $table->json('seats_snapshot')->nullable();

            // snapshot foods (array JSON)
            $table->json('foods_snapshot')->nullable();

            $table->dateTime('expires_at')->nullable();

            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('showtime_id')
                ->references('showtime_id')->on('showtimes')
                ->onDelete('restrict');

            $table->foreign('web_user_id')
                ->references('web_user_id')->on('web_users')
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
        Schema::dropIfExists('bookings');
    }
};
