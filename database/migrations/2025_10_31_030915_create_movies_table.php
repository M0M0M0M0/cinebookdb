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
        Schema::create('movies', function (Blueprint $table) {
            // Khóa chính
            $table->id('movie_id'); 

            // Cột dữ liệu
            $table->string('original_language', 10)->nullable();
            $table->string('original_title', 255);
            $table->string('overview', 2000)->nullable();
            $table->string('poster_path', 255)->nullable();
            $table->date('release_date')->nullable();
            $table->string('title', 255);
            $table->decimal('vote_average', 3, 1)->nullable();
            $table->integer('duration')->nullable();
            $table->string('trailer_link', 255)->nullable();
            $table->timestamps();         
            $table->unique(['title', 'release_date'], 'UQ_Movie_Title_Release');
        });
        DB::statement("
            ALTER TABLE movies
            ADD CONSTRAINT chk_original_language
            CHECK (original_language IS NULL OR LENGTH(original_language) >= 2)
        ");

        DB::statement("
            ALTER TABLE movies
            ADD CONSTRAINT chk_original_title
            CHECK (LENGTH(original_title) >= 1)
        ");

        DB::statement("
            ALTER TABLE movies
            ADD CONSTRAINT chk_overview
            CHECK (overview IS NULL OR LENGTH(overview) >= 0)
        ");

        DB::statement("
            ALTER TABLE movies
            ADD CONSTRAINT chk_poster_path
            CHECK (
                poster_path IS NULL
                OR poster_path LIKE '/%'
                OR poster_path LIKE 'http%'
            )
        ");

        DB::statement("
            ALTER TABLE movies
            ADD CONSTRAINT chk_release_date
            CHECK (release_date IS NULL OR release_date <= CURRENT_DATE)
        ");

        DB::statement("
            ALTER TABLE movies
            ADD CONSTRAINT chk_vote_average
            CHECK (vote_average IS NULL OR (vote_average >= 0 AND vote_average <= 10))
        ");

        DB::statement("
            ALTER TABLE movies
            ADD CONSTRAINT chk_duration
            CHECK (duration IS NULL OR (duration > 0 AND duration <= 600))
        ");

        DB::statement("
            ALTER TABLE movies
            ADD CONSTRAINT chk_trailer_link
            CHECK (trailer_link IS NULL OR trailer_link LIKE 'http%')
        ");
    
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movies');
    }
};
