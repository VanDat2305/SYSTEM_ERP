<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Kiểm tra xem bảng `users` có tồn tại không
        if (!Schema::hasTable('users')) {
            return;
        }

        // 2. Tạo bảng mới với UUID
        Schema::create('users_new', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('(UUID())'));
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
        });

        // 3. Chỉ chuyển dữ liệu nếu bảng `users` có dữ liệu
        if (DB::table('users')->exists()) {
            DB::statement('INSERT INTO users_new (id, name, email, email_verified_at, password, created_at, updated_at) 
                           SELECT UUID(), name, email, email_verified_at, password, created_at, updated_at FROM users');
        }

        // 4. Xóa bảng `users` cũ
        Schema::dropIfExists('users');

        // 5. Đổi tên bảng mới thành `users`
        Schema::rename('users_new', 'users');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
