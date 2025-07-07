<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('package_notify_logs', function (Blueprint $table) {
            $table->id();
            $table->char('order_detail_id', 36);
            $table->char('customer_id', 36);
            $table->string('type'); // warning, expired
            $table->string('milestone'); // 30days, 7days, 1day, low_quota, quota_0, expired
            $table->timestamp('sent_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('package_notify_logs');
    }
};
