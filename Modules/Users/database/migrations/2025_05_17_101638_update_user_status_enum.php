<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY status ENUM('active', 'inactive', 'pending', 'suspended', 'banned', 'deleted') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Khôi phục lại enum cũ
        DB::statement("ALTER TABLE users MODIFY status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending'");
    }
};

