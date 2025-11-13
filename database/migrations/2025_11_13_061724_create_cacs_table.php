<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cacs', function (Blueprint $table) {
            $table->id('cac_id'); // ✅ Tự tăng
            $table->unsignedBigInteger('tmdb_id')->unique()->index(); // ID gốc từ TMDB

            // 🧍 Thông tin cơ bản (chung cho cả cast & crew)
            $table->boolean('adult')->default(false);
            $table->tinyInteger('gender')->nullable(); // 0,1,2 theo TMDB
            $table->string('known_for_department')->nullable();
            $table->string('name')->nullable();
            $table->string('original_name')->nullable();
            $table->float('popularity')->nullable();
            $table->string('profile_path')->nullable();

            // 🧠 Một số field bổ sung dành cho cả 2 loại
            $table->string('character')->nullable(); // nếu là cast
            $table->string('credit_id')->nullable();
            $table->integer('cast_order')->nullable(); // nếu là cast
            $table->string('department')->nullable(); // nếu là crew
            $table->string('job')->nullable(); // nếu là crew

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cacs');
    }
};
