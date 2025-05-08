<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('status')->default('active')->after('guard_name'); // Hoặc dùng enum hoặc boolean tùy thích
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('status')->default('active')->after('guard_name'); // Cũng vậy
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
