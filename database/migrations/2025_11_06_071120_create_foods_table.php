<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('foods', function (Blueprint $table) {
            // ID tự tăng và Khóa chính
            $table->id('food_id');

            // Tên đồ ăn/thức uống (Bắt buộc)
            $table->string('food_name', 100)->unique();

            // Mô tả
            $table->text('description')->nullable();

            // Giá tiền cơ sở (decimal để lưu tiền tệ chính xác)
            $table->decimal('base_price', 10, 2)->nullable(false);

            // Trạng thái (Ví dụ: 'AVAILABLE', 'OUT_OF_STOCK')
            $table->string('status', 50)->default('AVAILABLE');

            $table->timestamps();
        });

        // Ràng buộc kiểm tra (CHECK) cho giá tiền
        DB::statement("ALTER TABLE foods ADD CONSTRAINT CHK_Food_BasePrice CHECK (base_price >= 0)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('foods');
    }
};
