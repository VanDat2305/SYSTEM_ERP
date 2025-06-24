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
        Schema::create('customer_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('object_type', 30);
            $table->uuid('object_id');
            $table->string('action', 50);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('note')->nullable();
            $table->uuid('file_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('user_name', 100)->nullable();
            $table->timestamps();

            //indexes
            $table->index(['object_type', 'object_id', 'created_at'], 'idx_object_type_id_created_at');
            $table->index(['created_at']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //remove the indexes if they were created
        Schema::table('customer_logs', function (Blueprint $table) {
            $table->dropIndex('idx_object_type_id_created_at');
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id']);
        });
        Schema::dropIfExists('customer_logs');
    }
};
