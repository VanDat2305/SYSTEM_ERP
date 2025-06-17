<?php

// database/migrations/2024_06_14_000002_create_order_details_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('service_package_id');
            $table->string('package_code', 20);
            $table->string('package_name', 100);
            $table->decimal('base_price', 15, 2);
            $table->integer('quantity');
            $table->decimal('total_price', 15, 2);
            $table->string('currency', 10);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->boolean('tax_included')->default(false);
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->decimal('total_with_tax', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('service_package_id')->references('id')->on('service_packages');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
