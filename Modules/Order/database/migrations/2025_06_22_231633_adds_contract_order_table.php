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
            $table->uuid('contract_file_id')->nullable()->after('invoice_status');
            $table->string('contract_number')->nullable()->after('contract_file_id');
            $table->timestamp('contract_date')->nullable()->after('contract_number');
            $table->string('contract_status', 30)->default('draft')->after('contract_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'contract_file_id',
                'contract_number',
                'contract_date',
                'contract_status',
            ]);
        });
    }
};
