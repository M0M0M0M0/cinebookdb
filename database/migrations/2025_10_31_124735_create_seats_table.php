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
        Schema::create('seats', function (Blueprint $table) {
            $table->id('seat_id');                    
            $table->string('seat_row', 1);              
            $table->integer('seat_number');          
            $table->string('seat_type_id', 10);         
            $table->unsignedBigInteger('room_id');     
            $table->timestamps();                       
        });

        // Validate
        DB::statement("ALTER TABLE seats ADD CONSTRAINT CHK_Seat_Number CHECK (seat_number > 0)");

        // Fk
        Schema::table('seats', function (Blueprint $table) {
            $table->foreign('seat_type_id')->references('seat_type_id')->on('seat_types')->onDelete('restrict');
            $table->foreign('room_id')->references('room_id')->on('rooms')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seats');
    }
};
