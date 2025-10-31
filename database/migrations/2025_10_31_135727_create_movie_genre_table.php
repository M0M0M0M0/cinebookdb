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
        Schema::create('movie_genre', function (Blueprint $table) {
            $table->integer('movie_id');
            $table->integer('genre_id');

            $table->primary(['movie_id', 'genre_id']);

            $table->foreign('movie_id')
                ->references('movie_id')
                ->on('movies')
                ->onDelete('cascade');

            $table->foreign('genre_id')
                ->references('genre_id')
                ->on('genres')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movie_genre');
    }
};
