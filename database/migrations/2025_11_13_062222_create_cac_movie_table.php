<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cac_movie', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cac_id');   // ✅ Liên kết đến bảng cacs
            $table->Integer('movie_id'); // ✅ Liên kết đến bảng movies

            // ⚙️ Loại vai trò và chi tiết quan hệ
            $table->enum('role_type', ['cast', 'crew']);
            $table->string('credit_id')->nullable();
            $table->integer('cast_order')->nullable();
            $table->string('character')->nullable();
            $table->string('department')->nullable();
            $table->string('job')->nullable();

            $table->timestamps();

            // 🔒 Khóa ngoại
            $table->foreign('cac_id')
                ->references('cac_id')
                ->on('cacs')
                ->onDelete('cascade');

            $table->foreign('movie_id')
                ->references('movie_id')
                ->on('movies')
                ->onDelete('cascade');

            $table->unique(['cac_id', 'movie_id', 'role_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cac_movie');
    }
};
