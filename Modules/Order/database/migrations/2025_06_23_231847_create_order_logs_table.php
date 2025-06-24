<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();                  // Khóa chính UUID
            $table->uuid('order_id');                       // FK tới đơn hàng (UUID)
            $table->string('action', 50);                   // Hành động (create, update, payment, ...)
            $table->string('old_status', 30)->nullable();   // Trạng thái trước (nullable)
            $table->string('new_status', 30)->nullable();   // Trạng thái sau (nullable)
            $table->text('note')->nullable();               // Ghi chú (nullable)
            $table->uuid('file_id')->nullable();            // FK tới file đính kèm (UUID, nullable)
            $table->uuid('user_id')->nullable();            // FK tới user (UUID, nullable)
            $table->string('user_name', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();  // Thời gian tạo (default: now)
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_logs');
    }
};
