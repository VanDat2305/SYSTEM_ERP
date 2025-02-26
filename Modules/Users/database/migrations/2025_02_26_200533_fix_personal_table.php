<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn('tokenable_id'); // Xóa cột cũ
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->uuid('tokenable_id')->index(); // Thêm cột mới kiểu UUID
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn('tokenable_id'); // Xóa cột UUID nếu rollback
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->bigInteger('tokenable_id')->unsigned()->index(); // Khôi phục kiểu dữ liệu cũ
        });
    }

};
