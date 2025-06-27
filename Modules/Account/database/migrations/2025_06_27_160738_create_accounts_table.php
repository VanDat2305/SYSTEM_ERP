<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Sử dụng UUID làm khóa chính
            $table->uuid('erp_customer_id')->unique()->nullable();           // Định danh duy nhất giữa các hệ thống (mapping với ERP)
            $table->string('code')->unique()->nullable();         // Mã tài khoản nội bộ (tùy hệ thống)
            $table->string('name');                               // Tên doanh nghiệp/cá nhân
            $table->string('email')->unique();                    // Email liên hệ chính
            $table->string('phone')->nullable();                  // Số điện thoại liên hệ
            $table->string('tax_code')->nullable();               // Mã số thuế (nếu là doanh nghiệp)
            $table->string('address')->nullable();
            $table->string('type');           // Loại tài khoản: company/personal/...
            $table->string('password');
            $table->boolean('is_active')->default(true);          // Trạng thái hoạt động
            $table->timestamp('activated_at')->nullable();        // Thời điểm kích hoạt
            $table->timestamps();                                 // created_at, updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('accounts');
    }
};
