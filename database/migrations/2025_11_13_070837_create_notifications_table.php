<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('notification_id')->primary();
            $table->uuid('web_user_id');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->foreign('web_user_id')
                ->references('web_user_id')
                ->on('web_users')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};


