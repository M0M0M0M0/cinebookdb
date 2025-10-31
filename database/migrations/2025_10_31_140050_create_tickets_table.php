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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id('ticket_id');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('seat_id');
            $table->decimal('base_price_snapshot', 12, 2);
            $table->string('seat_type_id_snapshot', 10);
            $table->decimal('seat_type_price_snapshot', 12, 2);
            $table->string('day_modifier_id_snapshot', 10);
            $table->decimal('day_modifier_snapshot', 8, 2);
            $table->string('time_slot_modifier_id_snapshot', 10);
            $table->decimal('time_slot_modifier_snapshot', 12, 2);
            $table->decimal('final_ticket_price', 12, 2);
            $table->timestamps();

            $table->foreign('booking_id')
                ->references('booking_id')
                ->on('bookings')
                ->onDelete('cascade');

            $table->foreign('seat_id')
                ->references('seat_id')
                ->on('seats')
                ->onDelete('restrict');
        });

        DB::statement("ALTER TABLE tickets ADD CONSTRAINT CHK_Ticket_Price CHECK (final_ticket_price >= 0)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets');
    }
};
