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
            Schema::table('files', function (Blueprint $table) {
            $table->uuid('object_id')->nullable()->after('folder_id');
            $table->string('document_type', 255)->nullable();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('object_id');
            $table->dropColumn('document_type');
        });
    }
};
