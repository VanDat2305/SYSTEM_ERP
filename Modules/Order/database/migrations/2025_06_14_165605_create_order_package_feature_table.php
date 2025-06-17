<?php

// database/migrations/2024_06_14_000003_create_order_package_features_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_package_features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_detail_id');
            $table->string('feature_key', 50);
            $table->string('feature_name', 100);
            $table->string('feature_type', 20);
            $table->string('unit', 20)->nullable();
            $table->decimal('limit_value', 10, 2)->nullable();
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_customizable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_detail_id')->references('id')->on('order_details');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_package_features');
    }
};
