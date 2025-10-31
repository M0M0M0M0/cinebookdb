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
        Schema::create('staffs', function (Blueprint $table) {
            $table->uuid('staff_id')->primary();
            $table->string('full_name', 100);
            $table->date('date_of_birth');
            $table->string('address', 255);
            $table->string('phone_number', 20);
            $table->string('email', 50)->unique();
            $table->string('password_hash', 255);
            $table->timestamps(); 
            $table->string('role_id', 10);
        });

        Schema::table('staffs', function (Blueprint $table) {
            $table->foreign('role_id')->references('role_id')->on('roles')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staffs');
    }
};
