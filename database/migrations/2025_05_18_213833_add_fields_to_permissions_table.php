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
        Schema::table('roles', function (Blueprint $table) {
            $table->string('title')->nullable()->after('name');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('title')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('title');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
