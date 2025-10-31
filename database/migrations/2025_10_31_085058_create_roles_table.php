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
       Schema::create('roles', function (Blueprint $table) {
            $table->string('role_id', 10)->primary()->check('LENGTH(role_id) >= 1');
            $table->string('role_name', 50)->check('LENGTH(role_name) >= 1');
            $table->string('role_description', 255)->check('LENGTH(role_description) >= 1');
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
        Schema::dropIfExists('roles');
    }
};
