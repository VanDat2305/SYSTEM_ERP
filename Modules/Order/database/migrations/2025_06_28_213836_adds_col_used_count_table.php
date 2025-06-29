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
        Schema::table('order_package_features', function (Blueprint $table) {
            $table->decimal('used_count', 10, 2)->default(0)->after('limit_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_package_features', function (Blueprint $table) {
            $table->dropColumn('used_count');
        });
    }
};
