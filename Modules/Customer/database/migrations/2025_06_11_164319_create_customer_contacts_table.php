<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->string('contact_type', 10);
            $table->string('value');
            $table->string('label', 20)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('note', 100)->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->index(['customer_id', 'contact_type']);
            $table->index(['customer_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_contacts');
    }
};
