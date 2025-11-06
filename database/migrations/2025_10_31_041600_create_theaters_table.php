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
        Schema::create('theaters', function (Blueprint $table) {
            $table->id('theater_id');
            $table->string('theater_name', 100)->check('LENGTH(theater_name) >= 1');
            $table->string('theater_address', 255)->check('LENGTH(theater_address) >= 1');
            $table->string('theater_city', 100)->check('LENGTH(theater_city) >= 1');
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
        Schema::dropIfExists('theaters');
    }
};
