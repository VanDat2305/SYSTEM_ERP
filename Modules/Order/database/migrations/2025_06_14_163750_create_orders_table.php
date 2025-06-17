<?php

// database/migrations/2024_06_14_000001_create_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_code', 50);
            $table->uuid('customer_id');
            $table->uuid('opportunity_id')->nullable();
            $table->string('order_status', 20);
            // $table->date('order_date');
            $table->string('currency', 10)->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->string('billing_cycle', 20)->nullable();
            $table->uuid('contract_id')->nullable();
            $table->uuid('team_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // FK - Bạn có thể bật hoặc tắt tùy dữ liệu demo
            $table->foreign('customer_id')->references('id')->on('customers');
            // $table->foreign('opportunity_id')->references('id')->on('opportunities');
            // $table->foreign('contract_id')->references('id')->on('contracts');
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

