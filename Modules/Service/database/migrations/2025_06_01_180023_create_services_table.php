<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
 
        Schema::create('service_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type_service', 20);
            $table->string('customer_type', 20);
            $table->string('package_code', 20)->unique();
            $table->string('package_name', 100);
            $table->decimal('base_price', 12, 2);
            $table->string('billing_cycle', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_package_features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('package_id')->constrained('service_packages')->onDelete('cascade');
            $table->string('feature_key', 50);
            $table->string('feature_name', 100);
            $table->string('feature_type', 20);
            $table->string('unit', 20)->nullable();
            $table->decimal('limit_value', 10, 2)->nullable();
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_customizable')->default(false);
            $table->integer('display_order');
            $table->timestamps();
            
            $table->index('package_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_packages');
        Schema::dropIfExists('service_package_features');
    }
};
