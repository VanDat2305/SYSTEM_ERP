<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('customer_type', ['INDIVIDUAL', 'ORGANIZATION']);
            $table->string('full_name')->nullable();
            $table->string('short_name', 100)->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('tax_code', 30)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('identity_type', 15)->nullable();
            $table->string('identity_number', 50)->nullable();
            $table->string('position', 100)->nullable();
            $table->string('website')->nullable();
            $table->uuid('team_id')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['customer_type', 'is_active']);
            $table->index('team_id');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};