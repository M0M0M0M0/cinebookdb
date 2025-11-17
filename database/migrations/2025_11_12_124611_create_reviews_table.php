<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // --- Tùy chỉnh cho User  ---
            $table->string('web_user_id', 36)->nullable();

            $table->string('staff_id', 36)->nullable();

            // --- Tùy chỉnh cho Movie  ---
            $table->integer('movie_id');

            $table->tinyInteger('rating')->unsigned();
            $table->text('comment')->nullable();

            $table->timestamps();

            // --- Định nghĩa khóa ngoại ---

            $table->foreign('web_user_id')
                  ->references('web_user_id') // Tham chiếu đến web_user_id
                  ->on('web_users') // trên bảng web_users
                  ->onDelete('cascade');

            $table->foreign('staff_id')
            ->references('staff_id')
            ->on('staffs')
            ->onDelete('cascade');

            
            // Tham chiếu đến 'movie_id'
            $table->foreign('movie_id')
                  ->references('movie_id') // 'movie_id'
                  ->on('movies') // trên bảng 'movies'
                  ->onDelete('cascade');

            $table->unique(['web_user_id', 'movie_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
