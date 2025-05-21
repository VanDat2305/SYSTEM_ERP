<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('objects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('object_type_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->uuid('created_by')->nullable();
            $table->uuid('tenant_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->integer('order')->nullable();   
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->foreign('object_type_id')->references('id')->on('object_types')->onDelete('cascade');
            $table->unique(['object_type_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objects');
    }
};
