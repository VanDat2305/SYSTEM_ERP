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
 
        Schema::table('service_packages', function (Blueprint $table) {
            $table->string('description')->nullable()->after('package_name');
            $table->string('currency', 10)->after('base_price');
            $table->integer('display_order')->default(0)->nullable()->after('is_active');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('currency');
            $table->dropColumn('display_order');
        });
    }
};
