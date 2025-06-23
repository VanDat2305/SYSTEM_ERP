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
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('invoice_file_id')->nullable();
            $table->timestamp('invoice_exported_at')->nullable()->after('invoice_file_id');
            $table->string('invoice_number')->nullable()->after('invoice_exported_at');
            $table->string('invoice_status', 30)->default('pending')->after('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_file_id',
                'invoice_exported_at',
                'invoice_number',
                'invoice_status',
            ]);
        });
    }
};
