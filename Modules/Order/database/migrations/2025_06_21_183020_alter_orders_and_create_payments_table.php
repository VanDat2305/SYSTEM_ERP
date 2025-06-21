<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('unpaid'); // unpaid, paid, failed
            $table->string('payment_method', 50)->nullable();        // vnpay, cash, etc.
            $table->timestamp('paid_at')->nullable();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->decimal('amount_paid', 15, 2);
            $table->timestamp('payment_date')->nullable();
            $table->string('payment_method', 50)->nullable(); // vnpay, cash, bank_transfer, etc.
            $table->string('status', 20); // successful, failed
            $table->string('payment_reference')->nullable(); // txn_ref from VNPAY
            $table->json('raw_response')->nullable();

            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_method', 'paid_at']);
        });

        Schema::dropIfExists('payments');
    }
};
