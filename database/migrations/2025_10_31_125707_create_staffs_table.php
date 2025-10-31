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
            $table->uuid('staff_id')->primary()->default(DB::raw('(NEWID())'));
            $table->string('full_name', 100);
            $table->date('date_of_birth');
            $table->string('address', 255);
            $table->string('phone_number', 20);
            $table->string('email', 50)->unique();
            $table->string('password_hash', 255);
            $table->timestamps(); 
            $table->string('role_id', 10);
        });

        DB::statement("ALTER TABLE staffs ADD CONSTRAINT CHK_Staff_FullName CHECK (LEN(full_name) >= 1)");
        DB::statement("ALTER TABLE staffs ADD CONSTRAINT CHK_Staff_Address CHECK (LEN(address) >= 1)");
        DB::statement("ALTER TABLE staffs ADD CONSTRAINT CHK_Staff_Phone CHECK (LEN(phone_number) >= 1)");

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
