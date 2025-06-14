<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\table;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->string('status', 15)->default('new')->after('assigned_to');
        });
        
        // If you need to set the status for existing records, you can do it here
        // DB::table('customers')->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('assigned_to');
            $table->dropColumn('status'); // Xóa cột status nếu rollback
        });
        
    }
};
