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
        Schema::create('showtime_seat_type_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('showtime_id');
            $table->string('seat_type_id', 10);
            $table->decimal('custom_seat_price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->primary(['showtime_id', 'seat_type_id']);

            $table->foreign('showtime_id')
                ->references('showtime_id')
                ->on('showtimes')
                ->onDelete('restrict');

            $table->foreign('seat_type_id')
                ->references('seat_type_id')
                ->on('seat_types')
                ->onDelete('restrict');
        });

        DB::statement("ALTER TABLE showtime_seat_type_prices ADD CONSTRAINT CHK_CustomSeatPrice CHECK (custom_seat_price >= 0)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_showtime_seat_type_price');
    }
};
